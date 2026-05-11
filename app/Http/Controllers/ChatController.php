<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatMessage;
use App\Models\Job;

class ChatController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // PÁGINA DO CHAT
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe a página do chat.
     * Aceita ?job_id=X para carregar contexto de uma vaga específica.
     */
    public function index(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->company_id;
        $jobId     = $request->query('job_id');

        // Garante que a vaga pertence à empresa do usuário logado
        $job = $jobId
            ? Job::with('candidates')
                  ->where('id', $jobId)
                  ->where('company_id', $companyId)
                  ->first()
            : null;

        // Carrega histórico salvo no banco (últimas 50 mensagens, ordem cronológica)
        $history = ChatMessage::where('company_id', $companyId)
                              ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                              ->when(!$jobId, fn($q) => $q->whereNull('job_id'))
                              ->orderBy('created_at', 'asc')
                              ->take(50)
                              ->get();

        return view('chat', compact('history', 'job'));
    }

    // ══════════════════════════════════════════════════════════════
    // LIMPAR HISTÓRICO
    // ══════════════════════════════════════════════════════════════

    /**
     * Apaga todas as mensagens da conversa atual (por empresa e job_id).
     * Chamado via POST /chat/clear pelo botão "Limpar" da view.
     */
    public function clear(Request $request)
    {
        $user      = Auth::user();
        $companyId = $user->company_id;
        $jobId     = $request->input('job_id');

        ChatMessage::where('company_id', $companyId)
                   ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                   ->when(!$jobId, fn($q) => $q->whereNull('job_id'))
                   ->delete();

        return response()->json(['ok' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // ENVIO DE MENSAGEM + STREAMING DA IA
    // ══════════════════════════════════════════════════════════════

    /**
     * Recebe a mensagem do usuário, salva, chama a API do Groq
     * e retorna a resposta em streaming (Server-Sent Events).
     *
     * FLUXO:
     *   1. Salva mensagem do usuário no banco
     *   2. Busca histórico (já inclui a mensagem recém-salva)
     *   3. Sanitiza o histórico (garante alternância user/assistant)
     *   4. Monta system prompt com contexto da empresa/vaga
     *   5. Chama a API do Groq em modo stream
     *   6. Envia os chunks via SSE para o frontend
     *   7. Salva a resposta completa da IA no banco
     */
    public function send(Request $request)
    {
        $user      = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json(['error' => 'Usuário sem empresa associada.'], 422);
        }

        $request->validate([
            'message' => 'required|string|max:4000',
            'job_id'  => 'nullable|integer|exists:jobs,id',
        ]);

        $jobId   = $request->input('job_id');
        $userMsg = trim($request->input('message'));

        // ── 1. Salva mensagem do usuário ──────────────────────────
        ChatMessage::create([
            'company_id' => $companyId,
            'job_id'     => $jobId,
            'role'       => 'user',
            'content'    => $userMsg,
        ]);

        // ── 2. Busca histórico (desc → reverse para ordem cronológica) ──
        $rawHistory = ChatMessage::where('company_id', $companyId)
                                 ->when($jobId,  fn($q) => $q->where('job_id', $jobId))
                                 ->when(!$jobId, fn($q) => $q->whereNull('job_id'))
                                 ->orderBy('created_at', 'desc')
                                 ->take(40)
                                 ->get()
                                 ->reverse()
                                 ->values();

        // ── 3. Sanitiza + monta prompt ────────────────────────────
        $history      = $this->sanitizeHistory($rawHistory);
        $systemPrompt = $this->buildSystemPrompt($user, $jobId);

        // ── 4. Chave da API ───────────────────────────────────────
        // CORREÇÃO: lê direto do .env via env() ou config('services.groq.key')
        // Adicione em config/services.php: 'groq' => ['key' => env('GROQ_API_KEY')]
        $groqKey = config('services.groq.key') ?: env('GROQ_API_KEY');

        if (!$groqKey) {
            return response()->json(['error' => 'Chave da API Groq não configurada.'], 500);
        }

        // ══════════════════════════════════════════════════════════
        // STREAMING GROQ via SSE
        // ══════════════════════════════════════════════════════════
        return response()->stream(
            function () use ($history, $systemPrompt, $companyId, $jobId, $groqKey) {

                // Anti-buffer (necessário para artisan serve / Windows)
                ini_set('output_buffering', 'off');
                ini_set('zlib.output_compression', 0);
                while (ob_get_level() > 0) ob_end_clean();
                ob_implicit_flush(true);
                set_time_limit(0);

                $fullResponse = '';

                $emit = static function (array $data): void {
                    echo 'data: ' . json_encode($data) . "\n\n";
                    flush();
                    if (function_exists('ob_flush')) @ob_flush();
                };

                try {
                    $payload = json_encode([
                        'model'       => 'llama-3.3-70b-versatile',
                        'max_tokens'  => 1024,
                        'stream'      => true,
                        'temperature' => 0.7,
                        'messages'    => array_merge(
                            [['role' => 'system', 'content' => $systemPrompt]],
                            $history
                        ),
                    ], JSON_UNESCAPED_UNICODE);

                    $ch = curl_init();

                    curl_setopt_array($ch, [
                        CURLOPT_URL            => 'https://api.groq.com/openai/v1/chat/completions',
                        CURLOPT_POST           => true,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . $groqKey,
                            'Content-Type: application/json',
                            'Accept: text/event-stream',
                        ],
                        CURLOPT_POSTFIELDS     => $payload,
                        CURLOPT_TIMEOUT        => 120,
                        CURLOPT_CONNECTTIMEOUT => 15,
                        CURLOPT_RETURNTRANSFER => false,  // NECESSÁRIO para WRITEFUNCTION funcionar
                        CURLOPT_FOLLOWLOCATION => true,

                        CURLOPT_WRITEFUNCTION  => function ($ch, $rawChunk) use (&$fullResponse, $emit) {
                            foreach (explode("\n", $rawChunk) as $line) {
                                $line = trim($line);
                                if (!str_starts_with($line, 'data: ')) continue;

                                $json = substr($line, 6);
                                if ($json === '[DONE]') continue;

                                $data = json_decode($json, true);
                                $content = $data['choices'][0]['delta']['content'] ?? '';

                                if ($content !== '') {
                                    $fullResponse .= $content;
                                    $emit(['chunk' => $content]);
                                }
                            }
                            return strlen($rawChunk);
                        },
                    ]);

                    curl_exec($ch);

                    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($curlError) {
                        $emit(['error' => 'Erro de rede: ' . $curlError]);
                    } elseif ($httpCode !== 200 && $fullResponse === '') {
                        $emit(['error' => "Erro Groq (HTTP {$httpCode}). Verifique a chave de API."]);
                    }

                } catch (\Throwable $e) {
                    $emit(['error' => 'Erro inesperado: ' . $e->getMessage()]);
                }

                // ── Salva resposta completa no banco ──────────────
                if ($fullResponse !== '') {
                    ChatMessage::create([
                        'company_id' => $companyId,
                        'job_id'     => $jobId,
                        'role'       => 'assistant',
                        'content'    => $fullResponse,
                    ]);
                }

                $emit(['done' => true]);
                echo "data: [DONE]\n\n";
                flush();
            },
            200,
            [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache, no-store, must-revalidate',
                'X-Accel-Buffering' => 'no',
                'Connection'        => 'keep-alive',
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════

    /**
     * Sanitiza o histórico garantindo alternância user/assistant.
     * A API do Groq rejeita mensagens consecutivas do mesmo role.
     *
     * @param  \Illuminate\Support\Collection  $raw  Coleção de ChatMessage (ou array)
     * @return array<int, array{role: string, content: string}>
     */
    private function sanitizeHistory($raw): array
    {
        $result   = [];
        $lastRole = null;

        foreach ($raw as $msg) {
            $role    = is_array($msg) ? $msg['role']    : $msg->role;
            $content = is_array($msg) ? $msg['content'] : $msg->content;

            // Normaliza 'ai' → 'assistant' (compatibilidade com registros antigos)
            if ($role === 'ai') {
                $role = 'assistant';
            }

            // Ignora mensagens consecutivas do mesmo role
            if ($role === $lastRole) {
                continue;
            }

            $result[]  = ['role' => $role, 'content' => (string) $content];
            $lastRole  = $role;
        }

        return $result;
    }

    /**
     * Monta o system prompt injetando contexto da empresa e da vaga.
     *
     * @param  \App\Models\User  $user
     * @param  int|null          $jobId
     * @return string
     */
    private function buildSystemPrompt($user, ?int $jobId): string
    {
        $company = $user->company;

        $lines = [
            "Você é um assistente especialista em Recursos Humanos da plataforma RHMatch.",
            "Responda sempre em português do Brasil, de forma clara e objetiva.",
            "",
        ];

        // ── Contexto da empresa ───────────────────────────────────
        if ($company) {
            $lines[] = "## Contexto da empresa";
            $lines[] = "Nome: {$company->razao_social}";

            if ($company->perfil_ritmo)     $lines[] = "Perfil de ritmo: {$company->perfil_ritmo}";
            if ($company->contexto_empresa) $lines[] = "Momento atual: {$company->contexto_empresa}";
            if ($company->valores)          $lines[] = "Valores: {$company->valores}";

            $lines[] = "";
        }

        // ── Contexto da vaga (quando houver job_id) ───────────────
        if ($jobId) {
            $job = Job::with(['candidates.personalityResults', 'leader'])->find($jobId);

            if ($job) {
                $lines[] = "## Vaga em contexto";
                $lines[] = "Cargo: {$job->titulo}";

                if ($job->descricao)         $lines[] = "Descrição: {$job->descricao}";
                if ($job->responsabilidades) $lines[] = "Responsabilidades: {$job->responsabilidades}";
                if ($job->jd_gerada)         $lines[] = "Job Description gerada:\n{$job->jd_gerada}";

                if ($job->perfil_ideal_json) {
                    $perfil  = is_string($job->perfil_ideal_json)
                        ? $job->perfil_ideal_json
                        : json_encode($job->perfil_ideal_json, JSON_UNESCAPED_UNICODE);
                    $lines[] = "Perfil psicométrico ideal: {$perfil}";
                }

                if ($job->leader) {
                    $lines[] = "Líder direto: {$job->leader->nome} — {$job->leader->cargo}";
                }

                $lines[] = "";
                $lines[] = "## Candidatos";

                foreach ($job->candidates as $c) {
                    $entry = "- {$c->nome}";

                    if ($c->personalityResults) {
                        $r = $c->personalityResults;
                        if ($r->disc_json)      $entry .= " | DISC: "      . json_encode($r->disc_json,      JSON_UNESCAPED_UNICODE);
                        if ($r->enneagram_json) $entry .= " | Eneagrama: " . json_encode($r->enneagram_json, JSON_UNESCAPED_UNICODE);
                        if ($r->mbti_json)      $entry .= " | 16P: "       . json_encode($r->mbti_json,      JSON_UNESCAPED_UNICODE);
                    }

                    $lines[] = $entry;
                }

                $lines[] = "";
            }
        }

        $lines[] = "Com base nesse contexto, responda as perguntas do usuário de RH.";
        $lines[] = "Se não tiver contexto suficiente, peça mais informações de forma gentil.";

        return implode("\n", $lines);
    }
}
