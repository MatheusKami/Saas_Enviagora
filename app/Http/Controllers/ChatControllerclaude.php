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
            ? Job::where('id', $jobId)
                  ->where('company_id', $companyId)
                  ->first()
            : null;

        // Carrega histórico salvo no banco (últimas 50 mensagens, ordem cronológica)
        $history = ChatMessage::where('company_id', $companyId)
                              ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                              ->orderBy('created_at', 'asc')
                              ->take(50)
                              ->get();

        return view('chat', compact('history', 'job'));
    }

    // ══════════════════════════════════════════════════════════════
    // ENVIO DE MENSAGEM + STREAMING DA IA
    // ══════════════════════════════════════════════════════════════

    /**
     * Recebe a mensagem do usuário, salva, chama a API do Claude
     * e retorna a resposta em streaming (Server-Sent Events).
     *
     * FLUXO CORRETO:
     *   1. Salva mensagem do usuário no banco
     *   2. Busca histórico (já inclui a mensagem recém-salva)
     *   3. Sanitiza o histórico (garante alternância user/assistant)
     *   4. Monta system prompt com contexto da empresa/vaga
     *   5. Chama a API da Claude em modo stream
     *   6. Envia os chunks via SSE para o frontend
     *   7. Salva a resposta completa da IA no banco
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:4000',
            'job_id'  => 'nullable|integer|exists:jobs,id',
        ]);

        $user      = Auth::user();
        $companyId = $user->company_id;
        $jobId     = $request->job_id;
        $userMsg   = trim($request->message);

        // 1. Salva mensagem do usuário
        ChatMessage::create([
            'company_id' => $companyId,
            'job_id'     => $jobId,
            'role'       => 'user',
            'content'    => $userMsg,
        ]);

        // 2. Busca histórico
        $rawHistory = ChatMessage::where('company_id', $companyId)
                                ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                                ->orderBy('created_at', 'desc')
                                ->take(40)
                                ->get()
                                ->reverse()
                                ->values();

        $history = $this->sanitizeHistory($rawHistory);

        // 3. System prompt
        $systemPrompt = $this->buildSystemPrompt($user, $jobId);

        // 4. Streaming com Groq
        return response()->stream(
            function () use ($history, $systemPrompt, $companyId, $jobId) {

                while (ob_get_level() > 0) ob_end_clean();
                set_time_limit(0);

                $fullResponse = '';

                $emit = function (array $data) {
                    echo 'data: ' . json_encode($data) . "\n\n";
                    flush();
                };

                try {
                    $ch = curl_init();

                    curl_setopt_array($ch, [
                        CURLOPT_URL            => 'https://api.groq.com/openai/v1/chat/completions',
                        CURLOPT_POST           => true,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . config('services.groq.key'),
                            'Content-Type: application/json',
                        ],
                        CURLOPT_POSTFIELDS     => json_encode([
                            'model'       => 'llama-3.3-70b-versatile',
                            'max_tokens'  => 1024,
                            'stream'      => true,
                            'temperature' => 0.7,
                            'messages'    => array_merge(
                                [['role' => 'system', 'content' => $systemPrompt]],
                                $history->toArray()
                            ),
                        ]),
                        CURLOPT_TIMEOUT        => 120,
                        CURLOPT_CONNECTTIMEOUT => 10,

                        CURLOPT_WRITEFUNCTION  => function ($ch, $rawChunk) use (&$fullResponse, $emit) {
                            $lines = explode("\n", $rawChunk);
                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (!str_starts_with($line, 'data: ')) continue;

                                $json = substr($line, 6);
                                if ($json === '[DONE]') continue;

                                $data = json_decode($json, true);
                                if (!is_array($data) || empty($data['choices'][0]['delta']['content'])) continue;

                                $content = $data['choices'][0]['delta']['content'];
                                $fullResponse .= $content;
                                $emit(['chunk' => $content]);
                            }
                            return strlen($rawChunk);
                        },
                    ]);

                    curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($curlError) {
                        $emit(['error' => 'Erro de rede: ' . $curlError]);
                    } elseif ($httpCode !== 200 && $fullResponse === '') {
                        $emit(['error' => "Erro Groq (HTTP {$httpCode})"]);
                    }

                } catch (\Exception $e) {
                    $emit(['error' => 'Erro inesperado: ' . $e->getMessage()]);
                }

                // Salva resposta
                if ($fullResponse) {
                    ChatMessage::create([
                        'company_id' => $companyId,
                        'job_id'     => $jobId,
                        'role'       => 'assistant',
                        'content'    => $fullResponse,
                    ]);
                }

                echo "data: [DONE]\n\n";
                flush();
            },
            200,
            [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache, no-store',
                'X-Accel-Buffering' => 'no',
                'Connection'        => 'keep-alive',
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // LIMPAR HISTÓRICO
    // ══════════════════════════════════════════════════════════════

    /**
     * Apaga o histórico do chat (por vaga ou geral da empresa).
     */
    public function clear(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $jobId     = $request->job_id;

        ChatMessage::where('company_id', $companyId)
                   ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                   ->delete();

        return response()->json(['ok' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════

    /**
     * Sanitiza o histórico de mensagens antes de enviar à API.
     *
     * A Anthropic exige alternância estrita entre 'user' e 'assistant'.
     * Este método percorre o histórico e, quando encontra dois roles
     * iguais consecutivos, mantém apenas o ÚLTIMO (mais recente).
     *
     * Exemplo de entrada inválida:
     *   user → user → assistant   (dois 'user' seguidos)
     *
     * Saída sanitizada:
     *   user → assistant
     *
     * @param  \Illuminate\Support\Collection $messages  Collection de ChatMessage
     * @return array  Array de ['role' => ..., 'content' => ...]
     */
    private function sanitizeHistory($messages): array
    {
        $sanitized = [];

        foreach ($messages as $msg) {
            $entry = [
                'role'    => $msg->role,
                'content' => $msg->content,
            ];

            // Se o último item do array tiver o mesmo role, substitui
            // pelo mais recente (mantém o contexto mais atual)
            if (!empty($sanitized) && end($sanitized)['role'] === $entry['role']) {
                array_pop($sanitized);
            }

            $sanitized[] = $entry;
        }

        // A API da Anthropic exige que a primeira mensagem seja 'user'.
        // Remove qualquer 'assistant' que tenha ficado no início.
        while (!empty($sanitized) && $sanitized[0]['role'] !== 'user') {
            array_shift($sanitized);
        }

        return $sanitized;
    }

    /**
     * Monta o system prompt injetando contexto da empresa e da vaga.
     *
     * O system prompt é enviado separado das mensagens na API da Anthropic
     * e define o comportamento/personalidade da IA para aquela sessão.
     *
     * @param  \App\Models\User  $user
     * @param  int|null          $jobId
     * @return string
     */
    private function buildSystemPrompt($user, ?int $jobId): string
    {
        $company = $user->company; // relação User→Company

        // Instruções base do assistente
        $prompt  = "Você é um assistente especialista em Recursos Humanos da plataforma RHMatch.\n";
        $prompt .= "Responda sempre em português do Brasil, de forma clara e objetiva.\n\n";

        // ── Contexto da empresa ────────────────────────────────
        if ($company) {
            $prompt .= "## Contexto da empresa\n";
            $prompt .= "Nome: {$company->razao_social}\n";

            if ($company->perfil_ritmo)     $prompt .= "Perfil de ritmo: {$company->perfil_ritmo}\n";
            if ($company->contexto_empresa) $prompt .= "Momento atual: {$company->contexto_empresa}\n";
            if ($company->valores)          $prompt .= "Valores: {$company->valores}\n";

            $prompt .= "\n";
        }

        // ── Contexto da vaga (quando houver job_id) ────────────
        if ($jobId) {
            $job = Job::with(['candidates.personalityResults', 'leader'])
                      ->find($jobId);

            if ($job) {
                $prompt .= "## Vaga em contexto\n";
                $prompt .= "Cargo: {$job->titulo}\n";

                if ($job->descricao)         $prompt .= "Descrição: {$job->descricao}\n";
                if ($job->responsabilidades) $prompt .= "Responsabilidades: {$job->responsabilidades}\n";
                if ($job->jd_gerada)         $prompt .= "Job Description gerada:\n{$job->jd_gerada}\n";

                // Perfil psicométrico ideal (JSON → string)
                if ($job->perfil_ideal_json) {
                    $perfil = is_string($job->perfil_ideal_json)
                        ? $job->perfil_ideal_json
                        : json_encode($job->perfil_ideal_json, JSON_UNESCAPED_UNICODE);
                    $prompt .= "Perfil psicométrico ideal: {$perfil}\n";
                }

                // Líder direto da vaga
                if ($job->leader) {
                    $prompt .= "Líder direto: {$job->leader->nome} — {$job->leader->cargo}\n";
                }

                // Lista de candidatos com resultados de personalidade
                $prompt .= "\n## Candidatos\n";
                foreach ($job->candidates as $c) {
                    $prompt .= "- {$c->nome}";

                    if ($c->personalityResults) {
                        $r = $c->personalityResults;

                        if ($r->disc_json)      $prompt .= " | DISC: "       . json_encode($r->disc_json,     JSON_UNESCAPED_UNICODE);
                        if ($r->enneagram_json) $prompt .= " | Eneagrama: "  . json_encode($r->enneagram_json, JSON_UNESCAPED_UNICODE);
                        if ($r->mbti_json)      $prompt .= " | 16P: "        . json_encode($r->mbti_json,     JSON_UNESCAPED_UNICODE);
                    }

                    $prompt .= "\n";
                }

                $prompt .= "\n";
            }
        }

        // Instrução final de comportamento
        $prompt .= "Com base nesse contexto, responda as perguntas do usuário de RH. ";
        $prompt .= "Se não tiver contexto suficiente, peça mais informações de forma gentil.";

        return $prompt;
    }


}
