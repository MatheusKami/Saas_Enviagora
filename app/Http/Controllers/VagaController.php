<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Job;
use App\Models\Candidate;
use App\Models\MatchReport;
use App\Models\OrganogramaNode;
use App\Models\PersonalityResult;
use App\Models\TestLink;

class VagaController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /vagas
    // Lista todas as vagas da empresa do usuário logado
    // ─────────────────────────────────────────────────────────
    public function index()
    {
        $vagas = Job::where('company_id', Auth::user()->company_id)
            ->withCount('candidates') // conta candidatos sem carregar todos
            ->latest()
            ->get();

        return view('vagas.index', compact('vagas'));
    }

    // ─────────────────────────────────────────────────────────
    // GET /vagas/nova-manual
    // Formulário para criar vaga e adicionar candidatos manualmente
    // ─────────────────────────────────────────────────────────
    public function nova_vaga()
    {
        $companyId = Auth::user()->company_id;

        // Carrega o organograma para popular o select de líder direto
        $lideres = OrganogramaNode::where('company_id', $companyId)
            ->orderBy('cargo')
            ->get();

        return view('vagas.create-manual', compact('lideres'));
    }

    // ─────────────────────────────────────────────────────────
    // GET /vagas/nova-ia
    // Formulário para criar vaga com ajuda da IA (gera JD etc.)
    // ─────────────────────────────────────────────────────────
    public function nova_ia()
    {
        $companyId = Auth::user()->company_id;

        $lideres = OrganogramaNode::where('company_id', $companyId)
            ->orderBy('cargo')
            ->get();

        return view('vagas.create-ia', compact('lideres'));
    }

    // ─────────────────────────────────────────────────────────
    // GET /vagas/{id}
    // Página de detalhe de uma vaga com candidatos e relatórios
    // ─────────────────────────────────────────────────────────
    public function show($id)
    {
        $companyId = Auth::user()->company_id;

        $vaga = Job::with([
            'candidates.personalityResults',
            'candidates.matchReport',
            'leader',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return view('vagas.show', compact('vaga'));
    }

    // ─────────────────────────────────────────────────────────
    // POST /vagas/gerar-jd
    // Recebe o briefing e chama o Groq para gerar a JD completa
    // Retorna JSON com: jd, salario, perfil_ideal, perguntas
    // ─────────────────────────────────────────────────────────
    public function gerarJD(Request $request)
    {
        $user    = Auth::user();
        $company = $user->company;

        $request->validate([
            'cargo'             => 'required|string|max:255',
            'responsabilidades' => 'required|string',
            'senioridade'       => 'nullable|string',
            'motivo'            => 'nullable|string',
            'metas'             => 'nullable|string',
            'lider_id'          => 'nullable|integer|exists:organograma_nodes,id',
        ]);

        // Monta o contexto da empresa para o prompt
        $contextoEmpresa = '';
        if ($company) {
            $contextoEmpresa .= "Empresa: {$company->razao_social}\n";
            if ($company->perfil_ritmo) {
                $contextoEmpresa .= "Perfil: {$company->perfil_ritmo}\n";
            }
            if ($company->contexto_empresa) {
                $contextoEmpresa .= "Contexto: {$company->contexto_empresa}\n";
            }
            if ($company->valores) {
                $valores = is_array($company->valores)
                    ? implode(', ', $company->valores)
                    : $company->valores;
                $contextoEmpresa .= "Valores: {$valores}\n";
            }
        }

        // Contexto do líder direto se informado
        $contextoLider = '';
        if ($request->lider_id) {
            $lider = OrganogramaNode::find($request->lider_id);
            if ($lider) {
                $contextoLider = "Líder direto: {$lider->nome} — {$lider->cargo}";
                // Se o líder tem resultados de personalidade, inclui
                if ($lider->personalityResults) {
                    $pr = $lider->personalityResults;
                    $contextoLider .= " | DISC: {$pr->disc_perfil} | MBTI: {$pr->mbti_tipo}";
                }
            }
        }

        // Prompt do sistema — instrui a IA a responder em JSON estruturado
        $systemPrompt = <<<PROMPT
Você é um especialista em RH e recrutamento para o mercado brasileiro.
Sua tarefa é gerar uma job description completa e profissional com base no briefing fornecido.

CONTEXTO DA EMPRESA:
{$contextoEmpresa}
{$contextoLider}

Responda APENAS com um JSON válido, sem nenhum texto fora do JSON, sem markdown, sem explicações.
O JSON deve ter exatamente essa estrutura:
{
  "jd": "Job description completa em português, formatada com parágrafos",
  "salary_texto": "Ex: R$ 8.000 – R$ 12.000",
  "salary_min": 8000,
  "salary_max": 12000,
  "perfil_ideal": {
    "disc": "D/I (perfil dominante e influente)",
    "mbti": "ENTJ ou ENFJ",
    "enneagram": "Tipo 3 ou 8",
    "descricao": "Perfil de uma linha"
  },
  "perguntas_triagem": [
    "Pergunta discriminatória 1?",
    "Pergunta discriminatória 2?",
    "Pergunta discriminatória 3?",
    "Pergunta discriminatória 4?",
    "Pergunta discriminatória 5?"
  ]
}
PROMPT;

        $userPrompt = <<<PROMPT
Gere a JD para a vaga abaixo:

Cargo: {$request->cargo}
Senioridade: {$request->senioridade}
Motivo de abertura: {$request->motivo}
Responsabilidades: {$request->responsabilidades}
Metas/OKRs: {$request->metas}
PROMPT;

        $resultado = $this->chamarGroq($systemPrompt, $userPrompt, 2000);

        if (isset($resultado['error'])) {
            return response()->json(['error' => $resultado['error']], 500);
        }

        // Tenta parsear o JSON retornado pela IA
        $json = $this->extrairJson($resultado['content']);

        if (!$json) {
            return response()->json([
                'error' => 'A IA retornou um formato inválido. Tente novamente.',
                'raw'   => $resultado['content'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data'    => $json,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /vagas
    // Salva a vaga no banco (depois de revisar o resultado da IA
    // ou ao submeter o formulário manual)
    // ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $request->validate([
            'titulo'            => 'required|string|max:255',
            'responsabilidades' => 'nullable|string',
            'metas'             => 'nullable|string',
            'motivo'            => 'nullable|string',
            'senioridade'       => 'nullable|string',
            'departamento'      => 'nullable|string',
            'lider_id'          => 'nullable|integer|exists:organograma_nodes,id',
            'jd_gerada'         => 'nullable|string',
            'salary_texto'      => 'nullable|string',
            'salary_min'        => 'nullable|numeric',
            'salary_max'        => 'nullable|numeric',
            'perfil_ideal_json' => 'nullable|array',
            'perguntas_triagem' => 'nullable|array',
            'modo_criacao'      => 'nullable|in:ia,manual',
        ]);

        $vaga = Job::create([
            'company_id'        => $companyId,
            'titulo'            => $request->titulo,
            'responsabilidades' => $request->responsabilidades,
            'metas'             => $request->metas,
            'motivo'            => $request->motivo,
            'senioridade'       => $request->senioridade,
            'departamento'      => $request->departamento,
            'lider_id'          => $request->lider_id ?: null,
            'jd_gerada'         => $request->jd_gerada,
            'salary_texto'      => $request->salary_texto,
            'salary_min'        => $request->salary_min,
            'salary_max'        => $request->salary_max,
            'perfil_ideal_json' => $request->perfil_ideal_json,
            'perguntas_triagem' => $request->perguntas_triagem,
            'modo_criacao'      => $request->modo_criacao ?? 'manual',
            'status'            => 'ativa',
        ]);

        return response()->json([
            'success'  => true,
            'vaga_id'  => $vaga->id,
            'redirect' => route('vagas.show', $vaga->id),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /vagas/{id}/candidatos
    // Adiciona um candidato à vaga (com upload de CV opcional)
    // ─────────────────────────────────────────────────────────
    public function adicionarCandidato(Request $request, $vagaId)
    {
        $companyId = Auth::user()->company_id;

        // Garante que a vaga pertence a essa empresa
        $vaga = Job::where('company_id', $companyId)->findOrFail($vagaId);

        $request->validate([
            'nome'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'linkedin_url'      => 'nullable|url|max:500',
            'cv'                => 'nullable|file|mimes:pdf|max:10240',
            'entrevista_texto'  => 'nullable|string',
            'disc_manual'       => 'nullable|string', // resultado digitado manualmente
            'mbti_manual'       => 'nullable|string',
            'enneagram_manual'  => 'nullable|string',
        ]);

        // Upload do currículo em PDF
        $cvUrl = null;
        if ($request->hasFile('cv')) {
            $cvUrl = $request->file('cv')->store("candidates/{$companyId}", 'public');
        }

        $candidate = Candidate::create([
            'job_id'            => $vaga->id,
            'company_id'        => $companyId,
            'nome'              => $request->nome,
            'email'             => $request->email,
            'linkedin_url'      => $request->linkedin_url,
            'cv_url'            => $cvUrl,
            'entrevista_texto'  => $request->entrevista_texto,
        ]);

        // Se o RH já tem resultados de personalidade para esse candidato, salva
        if ($request->disc_manual || $request->mbti_manual || $request->enneagram_manual) {
            PersonalityResult::create([
                'company_id'   => $companyId,
                'subject_id'   => $candidate->id,
                'subject_type' => 'candidate',
                // Salva como estrutura mínima — o frontend pode mandar mais detalhado
                'disc_json'       => $request->disc_manual
                    ? ['perfil' => strtoupper(trim($request->disc_manual)), 'manual' => true]
                    : null,
                'mbti_json'       => $request->mbti_manual
                    ? ['tipo' => strtoupper(trim($request->mbti_manual)), 'manual' => true]
                    : null,
                'enneagram_json'  => $request->enneagram_manual
                    ? ['tipo' => trim($request->enneagram_manual), 'manual' => true]
                    : null,
                'completed'       => false,
            ]);
        }

        return response()->json([
            'success'      => true,
            'candidate_id' => $candidate->id,
            'nome'         => $candidate->nome,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /vagas/{id}/match
    // Dispara a análise de match com IA para todos os candidatos da vaga
    // Retorna o relatório ranqueado
    // ─────────────────────────────────────────────────────────
    public function gerarMatch(Request $request, $vagaId)
    {
        $user      = Auth::user();
        $companyId = $user->company_id;
        $company   = $user->company;

        $vaga = Job::with([
            'candidates.personalityResults',
            'leader.personalityResults',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($vagaId);

        if ($vaga->candidates->isEmpty()) {
            return response()->json([
                'error' => 'Adicione pelo menos um candidato antes de gerar o match.'
            ], 422);
        }

        // Monta o contexto completo para a IA
        $contextoEmpresa = $this->montarContextoEmpresa($company);
        $contextoVaga    = $this->montarContextoVaga($vaga);
        $contextoLider   = $this->montarContextoLider($vaga->leader);

        // Monta a lista de candidatos com todos os dados disponíveis
        $candidatosTexto = '';
        foreach ($vaga->candidates as $i => $c) {
            $candidatosTexto .= "\n--- CANDIDATO " . ($i + 1) . " ---\n";
            $candidatosTexto .= "Nome: {$c->nome}\n";
            if ($c->personalityResults) {
                $pr = $c->personalityResults;
                if ($pr->disc_json)      $candidatosTexto .= "DISC: " . json_encode($pr->disc_json, JSON_UNESCAPED_UNICODE) . "\n";
                if ($pr->mbti_json)      $candidatosTexto .= "16P/MBTI: " . json_encode($pr->mbti_json, JSON_UNESCAPED_UNICODE) . "\n";
                if ($pr->enneagram_json) $candidatosTexto .= "Eneagrama: " . json_encode($pr->enneagram_json, JSON_UNESCAPED_UNICODE) . "\n";
            }
            if ($c->entrevista_texto) {
                $candidatosTexto .= "Transcrição de entrevista: " . substr($c->entrevista_texto, 0, 800) . "...\n";
            }
        }

        $systemPrompt = <<<PROMPT
Você é um especialista sênior em RH e psicometria, com ampla experiência no mercado brasileiro.
Analise os candidatos e gere um relatório de match completo e estruturado.

{$contextoEmpresa}
{$contextoLider}
{$contextoVaga}

CANDIDATOS:
{$candidatosTexto}

Responda APENAS com JSON válido, sem nenhum texto fora do JSON, sem markdown.
Use exatamente essa estrutura:
{
  "ranking": [
    {
      "posicao": 1,
      "candidato_nome": "Nome do candidato",
      "match_score": 85,
      "justificativa": "Por que é o mais indicado em 2-3 frases"
    }
  ],
  "relatorios": [
    {
      "candidato_nome": "Nome do candidato mais indicado (posição 1)",
      "pontos_fortes": ["Ponto forte 1 com exemplo prático", "Ponto forte 2"],
      "pontos_atencao": ["Ponto de atenção 1", "Ponto de atenção 2"],
      "como_delegar": "Como o líder deve delegar para esse perfil",
      "como_dar_feedback": "Como dar feedback para esse perfil",
      "fit_cultura": "Onde vai ter fit e onde pode ter fricção com a empresa",
      "perguntas_complementares": ["Pergunta para aprofundar 1?", "Pergunta 2?", "Pergunta 3?"],
      "desafio_case": "Descrição do desafio prático personalizado para o cargo (2-4 horas)",
      "plano_desenvolvimento": {
        "livros": [{"titulo": "Nome do livro", "justificativa": "Por que esse livro"}],
        "cursos": [{"titulo": "Nome do curso", "justificativa": "Por que esse curso"}],
        "evolucao_salarial": "Texto sobre revisão salarial vinculada a entregas"
      }
    }
  ]
}
PROMPT;

        $resultado = $this->chamarGroq($systemPrompt, "Gere o relatório de match agora.", 3000);

        if (isset($resultado['error'])) {
            return response()->json(['error' => $resultado['error']], 500);
        }

        $json = $this->extrairJson($resultado['content']);

        if (!$json) {
            return response()->json([
                'error' => 'Erro ao processar resposta da IA. Tente novamente.',
                'raw'   => $resultado['content'],
            ], 500);
        }

        // Salva os relatórios no banco por candidato
        if (!empty($json['ranking'])) {
            foreach ($json['ranking'] as $rankItem) {
                // Acha o candidato pelo nome (simplificado — idealmente por índice)
                $candidate = $vaga->candidates->firstWhere('nome', $rankItem['candidato_nome']);
                if (!$candidate) {
                    // Tenta matching parcial pelo nome
                    $candidate = $vaga->candidates->first(function ($c) use ($rankItem) {
                        return str_contains(strtolower($c->nome), strtolower(explode(' ', $rankItem['candidato_nome'])[0]));
                    });
                }
                if (!$candidate) continue;

                // Acha o relatório detalhado desse candidato
                $relatorioDetalhado = null;
                if (!empty($json['relatorios'])) {
                    foreach ($json['relatorios'] as $rel) {
                        if (str_contains(strtolower($rel['candidato_nome']), strtolower(explode(' ', $candidate->nome)[0]))) {
                            $relatorioDetalhado = $rel;
                            break;
                        }
                    }
                }

                // Salva ou atualiza o match report desse candidato
                MatchReport::updateOrCreate(
                    ['job_id' => $vaga->id, 'candidate_id' => $candidate->id],
                    [
                        'ranking_position' => $rankItem['posicao'],
                        'match_score'      => $rankItem['match_score'],
                        'relatorio_json'   => array_merge(
                            ['justificativa' => $rankItem['justificativa']],
                            $relatorioDetalhado ?? []
                        ),
                        'status' => 'done',
                    ]
                );
            }
        }

        return response()->json([
            'success'  => true,
            'data'     => $json,
            'vaga_id'  => $vaga->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /vagas/{id}/gerar-link-teste/{candidateId}
    // Gera um link único para o candidato realizar os testes psicométricos
    // ─────────────────────────────────────────────────────────
    public function gerarLinkTeste($vagaId, $candidateId)
    {
        $companyId = Auth::user()->company_id;

        $vaga      = Job::where('company_id', $companyId)->findOrFail($vagaId);
        $candidate = Candidate::where('job_id', $vaga->id)->findOrFail($candidateId);

        // Gera token único e seguro
        $token = Str::random(40);

        // Cria ou atualiza o link (prazo de 72h padrão)
        $link = TestLink::updateOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'company_id'  => $companyId,
                'token'       => $token,
                'type'        => 'candidate',
                'expires_at'  => now()->addHours(72),
                'completed_at' => null, // reseta se já tinha completado
            ]
        );

        // Atualiza o token no candidato também (atalho para busca rápida)
        $candidate->update([
            'test_link_token'    => $token,
            'test_link_expires_at' => $link->expires_at,
        ]);

        $url = route('teste.show', $token);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'url'     => $url,
            'expira'  => $link->expires_at->format('d/m/Y H:i'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────

    // Chama a API do Groq com system prompt + user prompt e retorna o texto da resposta
    private function chamarGroq(string $systemPrompt, string $userPrompt, int $maxTokens = 2000): array
    {
        $groqKey   = config('services.groq.key') ?: env('GROQ_API_KEY');
        $groqModel = config('services.groq.model') ?: env('GROQ_MODEL', 'llama-3.3-70b-versatile');

        if (!$groqKey) {
            return ['error' => 'GROQ_API_KEY não configurada no .env'];
        }

        $payload = json_encode([
            'model'    => $groqModel,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature'           => 0.4, // mais baixo para JSON estruturado ser mais estável
            'max_completion_tokens' => $maxTokens,
            'stream'                => false,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $groqKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['error' => "Erro de conexão: {$curlErr}"];
        }

        $data = json_decode($response, true);

        if ($status !== 200) {
            $msg = $data['error']['message'] ?? "HTTP {$status}";
            return ['error' => "Groq retornou erro: {$msg}"];
        }

        $content = $data['choices'][0]['message']['content'] ?? '';

        return ['content' => $content];
    }

    // Extrai o JSON de uma string que pode ter texto antes/depois ou markdown
    private function extrairJson(string $texto): ?array
    {
        // Remove blocos de markdown se a IA teimou em mandar ```json ... ```
        $texto = preg_replace('/```json\s*/i', '', $texto);
        $texto = preg_replace('/```\s*/i', '', $texto);
        $texto = trim($texto);

        // Tenta parsear direto
        $json = json_decode($texto, true);
        if ($json !== null) return $json;

        // Tenta achar o JSON dentro do texto (a IA às vezes bota texto antes)
        if (preg_match('/\{.*\}/s', $texto, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json !== null) return $json;
        }

        return null;
    }

    // Monta o bloco de contexto da empresa para o prompt
    private function montarContextoEmpresa($company): string
    {
        if (!$company) return '';
        $txt = "## EMPRESA\n";
        $txt .= "Nome: {$company->razao_social}\n";
        if ($company->perfil_ritmo)    $txt .= "Perfil: {$company->perfil_ritmo}\n";
        if ($company->contexto_empresa) $txt .= "Contexto: {$company->contexto_empresa}\n";
        if ($company->valores) {
            $v = is_array($company->valores) ? implode(', ', $company->valores) : $company->valores;
            $txt .= "Valores: {$v}\n";
        }
        return $txt;
    }

    // Monta o bloco de contexto da vaga para o prompt
    private function montarContextoVaga($vaga): string
    {
        $txt = "\n## VAGA\n";
        $txt .= "Cargo: {$vaga->titulo}\n";
        if ($vaga->senioridade)        $txt .= "Senioridade: {$vaga->senioridade}\n";
        if ($vaga->responsabilidades)  $txt .= "Responsabilidades: {$vaga->responsabilidades}\n";
        if ($vaga->metas)              $txt .= "Metas/OKRs: {$vaga->metas}\n";
        if ($vaga->jd_gerada)          $txt .= "JD gerada: {$vaga->jd_gerada}\n";
        if ($vaga->perfil_ideal_json) {
            $p = is_array($vaga->perfil_ideal_json)
                ? json_encode($vaga->perfil_ideal_json, JSON_UNESCAPED_UNICODE)
                : $vaga->perfil_ideal_json;
            $txt .= "Perfil ideal: {$p}\n";
        }
        return $txt;
    }

    // Monta o contexto do líder direto (se tiver personalidade registrada, inclui)
    private function montarContextoLider($lider): string
    {
        if (!$lider) return '';
        $txt = "\n## LÍDER DIRETO\n";
        $txt .= "Nome: {$lider->nome} — {$lider->cargo}\n";
        if ($lider->personalityResults) {
            $pr = $lider->personalityResults;
            $txt .= "DISC: {$pr->disc_perfil} | MBTI: {$pr->mbti_tipo} | Eneag: {$pr->enneagram_tipo}\n";
        }
        return $txt;
    }

    // =====================================================
    // MÉTODOS ADICIONADOS PARA COMPATIBILIDADE COM AS ROTAS
    // =====================================================

    public function create()
    {
        return $this->nova_vaga();
    }

    public function createIa()
    {
        return $this->nova_ia();
    }

    public function edit($id)
    {
        // TODO: Implementar edição de vaga
        return redirect()->route('jobs.show', $id)->with('info', 'Edição de vaga em desenvolvimento.');
    }

    public function update(Request $request, $id)
    {
        // TODO: Implementar atualização
        return response()->json(['success' => true, 'message' => 'Atualização em desenvolvimento.']);
    }

    public function destroy($id)
    {
        $companyId = Auth::user()->company_id;
        $vaga = Job::where('company_id', $companyId)->findOrFail($id);
        $vaga->delete();
        return redirect()->route('jobs.index')->with('success', 'Vaga excluída com sucesso.');
    }

    public function toggleStatus($id)
    {
        $companyId = Auth::user()->company_id;
        $vaga = Job::where('company_id', $companyId)->findOrFail($id);
        $vaga->status = $vaga->status === 'ativa' ? 'inativa' : 'ativa';
        $vaga->save();
        return redirect()->back()->with('success', 'Status alterado.');
    }

    public function candidatos($id)
    {
        $companyId = Auth::user()->company_id;
        $vaga = Job::with('candidates')->where('company_id', $companyId)->findOrFail($id);
        return view('vagas.candidatos', compact('vaga')); // ou redirecionar para show
    }
}