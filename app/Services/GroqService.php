<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Toda a lógica de IA fica aqui — mantendo Groq como combinado
// Groq é muito mais rápido que OpenAI e o preço é bom pro MVP
class GroqService
{
    // Modelo que uso — llama3-70b é o mais capaz do Groq atualmente
    private const MODEL = 'llama3-70b-8192';

    // URL base da API do Groq
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    private string $apiKey;

    public function __construct()
    {
        // Pego a chave do .env — nunca hardcodar a key no código
        $this->apiKey = config('services.groq.api_key', '');
    }

    // =======================================================
    // GERAR JOB DESCRIPTION
    // Recebe os dados da vaga + contexto da empresa e gera um JD completo
    // =======================================================
    public function gerarJobDescription(array $dadosVaga, Company $company): string
    {
        // Monto o contexto da empresa pra enriquecer o prompt
        $contextoEmpresa = $this->montarContextoEmpresa($company);

        $prompt = <<<EOT
Você é um especialista em Recursos Humanos e redação de Job Descriptions.

CONTEXTO DA EMPRESA:
{$contextoEmpresa}

DADOS DA VAGA:
- Cargo: {$dadosVaga['titulo']}
- Área: {$dadosVaga['area']}
- Nível: {$dadosVaga['nivel']} (Júnior/Pleno/Sênior)
- Modelo: {$dadosVaga['modelo_trabalho']}
- Faixa salarial: {$dadosVaga['salario_min']} a {$dadosVaga['salario_max']}
- Requisitos informados: {$dadosVaga['requisitos']}
- Diferenciais desejados: {$dadosVaga['diferenciais']}
- Responsabilidades principais: {$dadosVaga['responsabilidades']}

Gere uma Job Description completa, atrativa e inclusiva no seguinte formato:
1. Sobre a empresa (2-3 parágrafos baseados no contexto)
2. Sobre a vaga (o que fará no dia a dia)
3. Requisitos obrigatórios
4. Requisitos desejáveis
5. O que oferecemos
6. Como se candidatar

Use linguagem profissional mas acessível. Evite jargões e termos excludentes.
Adapte o tom ao ritmo da empresa ({$company->ritmo_trabalho}).
EOT;

        return $this->chamarApi([
            ['role' => 'system', 'content' => 'Você é um especialista em RH e redação de vagas. Escreva sempre em português brasileiro.'],
            ['role' => 'user', 'content' => $prompt],
        ], maxTokens: 2000);
    }

    // =======================================================
    // ANALISAR MATCH CANDIDATO × VAGA
    // Retorna um score e uma análise detalhada do fit
    // =======================================================
    public function analisarMatch(array $dadosCandidato, Job $vaga, Company $company): array
    {
        $contextoEmpresa = $this->montarContextoEmpresa($company);

        $prompt = <<<EOT
Você é um especialista em recrutamento e seleção. Analise o fit entre o candidato e a vaga.

EMPRESA E CULTURA:
{$contextoEmpresa}

VAGA:
- Título: {$vaga->titulo}
- Área: {$vaga->area}
- Nível: {$vaga->nivel}
- Requisitos obrigatórios: {$vaga->requisitos}
- Requisitos desejáveis: {$vaga->diferenciais}

CANDIDATO:
- Nome: {$dadosCandidato['nome']}
- Experiência: {$dadosCandidato['experiencia']} anos
- Cargo atual: {$dadosCandidato['cargo_atual']}
- Habilidades técnicas: {$dadosCandidato['habilidades']}
- Resumo profissional: {$dadosCandidato['resumo']}
- Resultados psicométricos: {$dadosCandidato['psicometrico'] ?? 'Não aplicado'}

Responda APENAS em JSON válido com este formato exato:
{
  "score": número de 0 a 100,
  "nivel_match": "Excelente|Bom|Regular|Baixo",
  "pontos_fortes": ["ponto1", "ponto2", "ponto3"],
  "pontos_atencao": ["ponto1", "ponto2"],
  "fit_cultural": "análise do fit com a cultura da empresa em 2-3 frases",
  "recomendacao": "Contratar|Avançar|Considerar|Descartar",
  "justificativa": "justificativa da recomendação em 3-4 frases",
  "perguntas_entrevista": ["pergunta1", "pergunta2", "pergunta3"]
}
EOT;

        $resposta = $this->chamarApi([
            ['role' => 'system', 'content' => 'Você é um especialista em recrutamento. Responda sempre em JSON válido e em português brasileiro.'],
            ['role' => 'user', 'content' => $prompt],
        ], maxTokens: 1500);

        // Tenta parsear o JSON — se falhar, retorna uma estrutura de erro amigável
        try {
            // Remove markdown se o modelo colocou ```json ... ```
            $json = preg_replace('/```json?\s*|\s*```/', '', $resposta);
            return json_decode(trim($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Groq retornou JSON inválido no match', ['resposta' => $resposta, 'erro' => $e->getMessage()]);
            return [
                'score'           => 0,
                'nivel_match'     => 'Erro',
                'pontos_fortes'   => [],
                'pontos_atencao'  => ['Erro ao processar análise. Tente novamente.'],
                'fit_cultural'    => 'Não foi possível analisar.',
                'recomendacao'    => 'Erro',
                'justificativa'   => $resposta, // Mostro a resposta bruta pra debug
                'perguntas_entrevista' => [],
            ];
        }
    }

    // =======================================================
    // CHAT ASSISTENTE DE RH
    // Responde perguntas sobre RH, candidatos e vagas
    // Mantém o histórico da conversa pra ter contexto
    // =======================================================
    public function chatAssistente(array $historico, string $mensagem, Company $company): string
    {
        $contextoEmpresa = $this->montarContextoEmpresa($company);

        // System prompt do assistente — define o comportamento dele
        $systemPrompt = <<<EOT
Você é um assistente especializado em Recursos Humanos chamado RHMate.
Você trabalha para a empresa {$company->display_name} e conhece bem sua cultura e contexto.

CONTEXTO DA EMPRESA:
{$contextoEmpresa}

Você pode ajudar com:
- Análise de candidatos e vagas
- Dicas de entrevista
- Redação de mensagens para candidatos
- Boas práticas de RH e recrutamento
- Interpretação de resultados psicométricos
- Estratégias de employer branding

Seja objetivo, prático e sempre em português brasileiro.
EOT;

        // Monto o array de mensagens com o histórico completo
        $mensagens = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Adiciono o histórico (limito a últimas 10 mensagens pra não explodir o contexto)
        $historicoPaginado = array_slice($historico, -10);
        foreach ($historicoPaginado as $msg) {
            $mensagens[] = [
                'role'    => $msg['role'],   // 'user' ou 'assistant'
                'content' => $msg['content'],
            ];
        }

        // Adiciono a mensagem atual do usuário
        $mensagens[] = ['role' => 'user', 'content' => $mensagem];

        return $this->chamarApi($mensagens, maxTokens: 1000);
    }

    // =======================================================
    // GERAR PERGUNTAS PSICOMÉTRICAS
    // Cria perguntas personalizadas baseadas no cargo e empresa
    // =======================================================
    public function gerarPerguntas(string $cargo, string $area, Company $company): array
    {
        $contexto = $this->montarContextoEmpresa($company);

        $prompt = <<<EOT
Gere 10 perguntas psicométricas para avaliar candidatos ao cargo de {$cargo} na área de {$area}.

CULTURA DA EMPRESA:
{$contexto}

As perguntas devem avaliar:
- Fit cultural com a empresa
- Estilo de trabalho e comunicação
- Tomada de decisão
- Resolução de problemas
- Trabalho em equipe

Responda em JSON:
{
  "perguntas": [
    {
      "id": 1,
      "texto": "...",
      "dimensao": "fit_cultural|trabalho_em_equipe|lideranca|resolucao_problemas|comunicacao",
      "opcoes": [
        {"valor": 1, "texto": "Discordo totalmente"},
        {"valor": 2, "texto": "Discordo"},
        {"valor": 3, "texto": "Neutro"},
        {"valor": 4, "texto": "Concordo"},
        {"valor": 5, "texto": "Concordo totalmente"}
      ]
    }
  ]
}
EOT;

        $resposta = $this->chamarApi([
            ['role' => 'system', 'content' => 'Responda sempre em JSON válido e em português brasileiro.'],
            ['role' => 'user', 'content' => $prompt],
        ], maxTokens: 2000);

        try {
            $json = preg_replace('/```json?\s*|\s*```/', '', $resposta);
            $data = json_decode(trim($json), true, 512, JSON_THROW_ON_ERROR);
            return $data['perguntas'] ?? [];
        } catch (\JsonException $e) {
            Log::error('Groq: erro ao gerar perguntas psicométricas', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    // =======================================================
    // Monta o contexto da empresa pra incluir nos prompts
    // Centralizo aqui pra não repetir em cada método
    // =======================================================
    private function montarContextoEmpresa(Company $company): string
    {
        $partes = [];

        $partes[] = "Nome: {$company->display_name}";

        if ($company->cidade && $company->estado) {
            $partes[] = "Localização: {$company->cidade}/{$company->estado}";
        }

        if ($company->modelo_trabalho) {
            $partes[] = "Modelo de trabalho: {$company->modelo_trabalho}";
        }

        if ($company->ritmo_trabalho) {
            $partes[] = "Ritmo: {$company->ritmo_trabalho}";
        }

        if ($company->cultura_empresa) {
            $partes[] = "Cultura: {$company->cultura_empresa}";
        }

        if ($company->valores_empresa) {
            $partes[] = "Valores: {$company->valores_empresa}";
        }

        if ($company->diferenciais_empresa) {
            $partes[] = "Benefícios e diferenciais: {$company->diferenciais_empresa}";
        }

        // Tamanho da empresa baseado nos colaboradores cadastrados
        if ($company->colaboradores_por_area) {
            $total = collect($company->colaboradores_por_area)->sum('quantidade');
            if ($total > 0) {
                $partes[] = "Tamanho: {$total} colaboradores";
            }
        }

        return implode("\n", $partes);
    }

    // =======================================================
    // Faz a chamada na API do Groq
    // Centralizo aqui pra ter um lugar só pra tratar erros
    // =======================================================
    private function chamarApi(array $mensagens, int $maxTokens = 1000): string
    {
        if (empty($this->apiKey)) {
            Log::warning('Groq API key não configurada. Adicione GROQ_API_KEY no .env');
            return 'Assistente de IA não configurado. Adicione a chave do Groq no arquivo .env.';
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)  // Groq é rápido, mas coloco 60s de timeout por segurança
                ->post(self::API_URL, [
                    'model'       => self::MODEL,
                    'messages'    => $mensagens,
                    'max_tokens'  => $maxTokens,
                    'temperature' => 0.7, // Equilíbrio entre criatividade e precisão
                ]);

            if ($response->failed()) {
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return "Erro ao comunicar com a IA. Código: {$response->status()}. Tente novamente.";
            }

            return $response->json('choices.0.message.content', 'Sem resposta.');
        } catch (\Exception $e) {
            Log::error('Exceção ao chamar Groq', ['mensagem' => $e->getMessage()]);
            return 'Erro interno ao acessar a IA. Verifique os logs.';
        }
    }
}
