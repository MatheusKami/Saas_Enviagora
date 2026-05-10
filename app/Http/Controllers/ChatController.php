<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\ChatMessage;
use App\Models\Job;

class ChatController extends Controller
{
    /**
     * Exibe a página do chat.
     * Aceita ?job_id=X para carregar contexto de uma vaga específica.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;   // já está correto
        $jobId    = $request->query('job_id');
        $job      = $jobId ? Job::where('id', $jobId)
                                  ->where('company_id', Auth::user()->company_id)
                                  ->first()
                           : null;

        // Histórico da conversa (últimas 50 mensagens)
        $history = ChatMessage::where('company_id', Auth::user()->company_id)
                              ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                              ->orderBy('created_at', 'asc')
                              ->take(50)
                              ->get();

        return view('chat', compact('history', 'job'));
    }

    /**
     * Recebe a mensagem do usuário, salva, chama a API do Claude
     * e retorna a resposta em streaming (Server-Sent Events).
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

        // 2. Monta histórico para enviar ao Claude (últimas 20 trocas)
        $history = ChatMessage::where('company_id', $companyId)
                              ->when($jobId, fn($q) => $q->where('job_id', $jobId))
                              ->orderBy('created_at', 'desc')
                              ->take(40)
                              ->get()
                              ->reverse()
                              ->map(fn($m) => [
                                  'role'    => $m->role,
                                  'content' => $m->content,
                              ])
                              ->values()
                              ->toArray();

        // 3. Monta system prompt com contexto da empresa e da vaga
        $systemPrompt = $this->buildSystemPrompt($user, $jobId);

        // 4. Streaming via Server-Sent Events
        return response()->stream(function () use ($history, $systemPrompt, $companyId, $jobId) {

            $fullResponse = '';

            try {
                $response = Http::withHeaders([
                    'x-api-key'         => config('services.anthropic.key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])->timeout(120)->withOptions(['stream' => true])
                  ->post('https://api.anthropic.com/v1/messages', [
                      'model'      => 'claude-sonnet-4-20250514',
                      'max_tokens' => 1000,
                      'stream'     => true,
                      'system'     => $systemPrompt,
                      'messages'   => $history,
                  ]);

                $body = $response->getBody();

                while (!$body->eof()) {
                    $line = $this->readLine($body);

                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);

                        if ($json === '[DONE]') break;

                        $data = json_decode($json, true);

                        // Extrai o delta de texto
                        if (isset($data['type']) && $data['type'] === 'content_block_delta') {
                            $chunk = $data['delta']['text'] ?? '';
                            if ($chunk !== '') {
                                $fullResponse .= $chunk;
                                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                                ob_flush();
                                flush();
                            }
                        }

                        // Fim do stream
                        if (isset($data['type']) && $data['type'] === 'message_stop') {
                            break;
                        }
                    }
                }

            } catch (\Exception $e) {
                echo "data: " . json_encode(['error' => 'Erro ao conectar com a IA. Tente novamente.']) . "\n\n";
                ob_flush();
                flush();
            }

            // 5. Salva resposta completa do assistente no banco
            if ($fullResponse) {
                ChatMessage::create([
                    'company_id' => $companyId,
                    'job_id'     => $jobId,
                    'role'       => 'assistant',
                    'content'    => $fullResponse,
                ]);
            }

            // Sinaliza fim do stream
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no', // desativa buffer do Nginx
        ]);
    }

    /**
     * Apaga o histórico do chat (por vaga ou geral).
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

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Monta o system prompt injetando contexto da empresa e da vaga.
     */
    private function buildSystemPrompt($user, ?int $jobId): string
    {
        
        $company = $user->company; // relação User->Company

        $prompt  = "Você é um assistente especialista em Recursos Humanos da plataforma RHMatch.\n";
        $prompt .= "Responda sempre em português do Brasil, de forma clara e objetiva.\n\n";

        if ($company) {
            $prompt .= "## Contexto da empresa\n";
            $prompt .= "Nome: {$company->razao_social}\n";
            if ($company->perfil_ritmo)    $prompt .= "Perfil: {$company->perfil_ritmo}\n";
            if ($company->contexto_empresa) $prompt .= "Momento atual: {$company->contexto_empresa}\n";
            if ($company->valores)         $prompt .= "Valores: {$company->valores}\n";
            $prompt .= "\n";
        }

        if ($jobId) {
            $job = Job::with(['candidates.personalityResults', 'leader'])
                      ->find($jobId);

            if ($job) {
                $prompt .= "## Vaga em contexto\n";
                $prompt .= "Cargo: {$job->titulo}\n";
                if ($job->descricao)        $prompt .= "Descrição: {$job->descricao}\n";
                if ($job->responsabilidades) $prompt .= "Responsabilidades: {$job->responsabilidades}\n";
                if ($job->jd_gerada)        $prompt .= "Job Description gerada:\n{$job->jd_gerada}\n";
                if ($job->perfil_ideal_json) {
                    $perfil = is_string($job->perfil_ideal_json)
                        ? $job->perfil_ideal_json
                        : json_encode($job->perfil_ideal_json, JSON_UNESCAPED_UNICODE);
                    $prompt .= "Perfil psicométrico ideal: {$perfil}\n";
                }

                if ($job->leader) {
                    $prompt .= "Líder direto: {$job->leader->nome} — {$job->leader->cargo}\n";
                }

                $prompt .= "\n## Candidatos\n";
                foreach ($job->candidates as $c) {
                    $prompt .= "- {$c->nome}";
                    if ($c->personalityResults) {
                        $r = $c->personalityResults;
                        if ($r->disc_json)      $prompt .= " | DISC: " . json_encode($r->disc_json, JSON_UNESCAPED_UNICODE);
                        if ($r->enneagram_json)  $prompt .= " | Eneagrama: " . json_encode($r->enneagram_json, JSON_UNESCAPED_UNICODE);
                        if ($r->mbti_json)       $prompt .= " | 16P: " . json_encode($r->mbti_json, JSON_UNESCAPED_UNICODE);
                    }
                    $prompt .= "\n";
                }
                $prompt .= "\n";
            }
        }

        $prompt .= "Com base nesse contexto, responda as perguntas do usuário de RH. ";
        $prompt .= "Se não tiver contexto suficiente, peça mais informações de forma gentil.";

        return $prompt;
    }

    /**
     * Lê uma linha do stream HTTP.
     */
    private function readLine($body): string
    {
        $line = '';
        while (!$body->eof()) {
            $char = $body->read(1);
            if ($char === "\n") break;
            $line .= $char;
        }
        return rtrim($line, "\r");
    }
}
