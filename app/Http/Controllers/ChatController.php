<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatMessage;
use App\Models\Job;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->company_id;
        $jobId     = $request->query('job_id');

        $job = $jobId
            ? Job::with(['candidates.personalityResults', 'leader'])
                ->where('id', $jobId)
                ->where('company_id', $companyId)
                ->first()
            : null;

        $history = ChatMessage::where('company_id', $companyId)
            ->when($jobId, fn ($q) => $q->where('job_id', $jobId))
            ->when(!$jobId, fn ($q) => $q->whereNull('job_id'))
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        return view('chat', compact('history', 'job'));
    }

    public function clear(Request $request)
    {
        $user      = Auth::user();
        $companyId = $user->company_id;
        $jobId     = $request->input('job_id');

        ChatMessage::where('company_id', $companyId)
            ->when($jobId, fn ($q) => $q->where('job_id', $jobId))
            ->when(!$jobId, fn ($q) => $q->whereNull('job_id'))
            ->delete();

        return response()->json([
            'ok' => true
        ]);
    }

    public function send(Request $request)
    {
        $user      = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'error' => 'Usuário sem empresa associada.'
            ], 422);
        }

        $request->validate([
            'message' => 'required|string|max:4000',
            'job_id'  => 'nullable|integer|exists:jobs,id',
        ]);

        $jobId   = $request->input('job_id');
        $userMsg = trim($request->input('message'));

        ChatMessage::create([
            'company_id' => $companyId,
            'job_id'     => $jobId,
            'role'       => 'user',
            'content'    => $userMsg,
        ]);

        $history      = $this->sanitizeHistory($companyId, $jobId);
        $systemPrompt = $this->buildSystemPrompt($user, $jobId);

        $groqKey = config('services.groq.key') ?: env('GROQ_API_KEY');

        $groqModel = config('services.groq.model')
            ?: env('GROQ_MODEL', 'llama-3.3-70b-versatile');

        if (!$groqKey) {
            return response()->json([
                'error' => 'GROQ_API_KEY não configurada.'
            ], 500);
        }

        return response()->stream(function () use (
            $history,
            $systemPrompt,
            $companyId,
            $jobId,
            $groqKey,
            $groqModel
        ) {

            ini_set('output_buffering', 'off');
            ini_set('zlib.output_compression', 0);
            ini_set('implicit_flush', 1);

            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            ob_implicit_flush(true);

            $fullResponse = '';

            $emit = function ($data) {

                echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

                @ob_flush();
                flush();
            };

            echo ":" . str_repeat(" ", 2048) . "\n";
            echo "retry: 1000\n\n";

            @ob_flush();
            flush();

            try {

                $payload = json_encode([
                    'model' => $groqModel,
                    'messages' => array_merge(
                        [
                            [
                                'role' => 'system',
                                'content' => $systemPrompt
                            ]
                        ],
                        $history
                    ),
                    'temperature' => 0.7,
                    'max_completion_tokens' => 1024,
                    'stream' => true,
                ], JSON_UNESCAPED_UNICODE);

                $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

                curl_setopt_array($ch, [

                    CURLOPT_POST => true,

                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $groqKey,
                        'Content-Type: application/json',
                        'Accept: text/event-stream',
                        'Cache-Control: no-cache',
                        'Connection: keep-alive',
                    ],

                    CURLOPT_POSTFIELDS => $payload,

                    CURLOPT_RETURNTRANSFER => false,

                    CURLOPT_TIMEOUT => 120,

                    CURLOPT_SSL_VERIFYPEER => false,

                    CURLOPT_BUFFERSIZE => 128,

                    CURLOPT_TCP_NODELAY => true,

                    CURLOPT_WRITEFUNCTION => function ($ch, $rawChunk) use (&$fullResponse, $emit) {

                        $lines = explode("\n", $rawChunk);

                        foreach ($lines as $line) {

                            $line = trim($line);

                            if (!str_starts_with($line, 'data: ')) {
                                continue;
                            }

                            $json = substr($line, 6);

                            if ($json === '[DONE]') {
                                return strlen($rawChunk);
                            }

                            $data = json_decode($json, true);

                            if (!$data) {
                                continue;
                            }

                            $content = $data['choices'][0]['delta']['content'] ?? '';

                            if ($content !== '') {

                                $fullResponse .= $content;

                                $emit([
                                    'chunk' => $content,
                                    'time' => now()->timestamp,
                                ]);
                            }
                        }

                        return strlen($rawChunk);
                    },
                ]);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {

                    $emit([
                        'error' => curl_error($ch)
                    ]);
                }

                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($status !== 200) {

                    $emit([
                        'error' => 'Erro HTTP: ' . $status
                    ]);
                }

                curl_close($ch);

            } catch (\Throwable $e) {

                $emit([
                    'error' => $e->getMessage()
                ]);
            }

            if (!empty(trim($fullResponse))) {

                ChatMessage::create([
                    'company_id' => $companyId,
                    'job_id'     => $jobId,
                    'role'       => 'assistant',
                    'content'    => $fullResponse,
                ]);
            }

            $emit([
                'done' => true
            ]);

            echo "data: [DONE]\n\n";

            @ob_flush();
            flush();

        }, 200, [

            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sanitizeHistory($companyId, $jobId)
    {
        $raw = ChatMessage::where('company_id', $companyId)
            ->when($jobId, fn ($q) => $q->where('job_id', $jobId))
            ->when(!$jobId, fn ($q) => $q->whereNull('job_id'))
            ->orderBy('created_at', 'desc')
            ->take(40)
            ->get()
            ->reverse()
            ->values();

        $result = [];

        $lastRole = null;

        foreach ($raw as $msg) {

            $role = $msg->role === 'ai'
                ? 'assistant'
                : $msg->role;

            if ($role === $lastRole) {
                continue;
            }

            $result[] = [
                'role' => $role,
                'content' => (string) $msg->content
            ];

            $lastRole = $role;
        }

        return $result;
    }

    private function buildSystemPrompt($user, ?int $jobId): string
    {
        $company = $user->company;

        $lines = [
            "Você é um assistente especialista em RH da plataforma RHMatch.",
            "Responda sempre em português do Brasil.",
            "Seja claro, direto e útil.",
            "",
        ];

        if ($company) {

            $lines[] = "## Empresa";
            $lines[] = "Nome: {$company->razao_social}";

            if ($company->perfil_ritmo) {

                $ritmo = is_array($company->perfil_ritmo)
                    ? json_encode($company->perfil_ritmo, JSON_UNESCAPED_UNICODE)
                    : $company->perfil_ritmo;

                $lines[] = "Perfil: {$ritmo}";
            }

            if ($company->contexto_empresa) {

                $contexto = is_array($company->contexto_empresa)
                    ? json_encode($company->contexto_empresa, JSON_UNESCAPED_UNICODE)
                    : $company->contexto_empresa;

                $lines[] = "Contexto: {$contexto}";
            }

            if ($company->valores) {

                $valores = is_array($company->valores)
                    ? json_encode($company->valores, JSON_UNESCAPED_UNICODE)
                    : $company->valores;

                $lines[] = "Valores: {$valores}";
            }

            $lines[] = "";
        }

        if ($jobId) {

            $job = Job::with([
                'candidates.personalityResults',
                'leader'
            ])->find($jobId);

            if ($job) {

                $lines[] = "## Vaga";
                $lines[] = "Cargo: {$job->titulo}";

                if ($job->descricao) {
                    $lines[] = "Descrição: {$job->descricao}";
                }

                if ($job->responsabilidades) {
                    $lines[] = "Responsabilidades: {$job->responsabilidades}";
                }

                if ($job->jd_gerada) {
                    $lines[] = "JD:\n{$job->jd_gerada}";
                }

                if ($job->perfil_ideal_json) {

                    $perfil = is_array($job->perfil_ideal_json)
                        ? json_encode($job->perfil_ideal_json, JSON_UNESCAPED_UNICODE)
                        : $job->perfil_ideal_json;

                    $lines[] = "Perfil ideal: {$perfil}";
                }

                if ($job->leader) {
                    $lines[] = "Líder: {$job->leader->nome}";
                }

                $lines[] = "";
                $lines[] = "## Candidatos";

                foreach ($job->candidates as $c) {

                    $entry = "- {$c->nome}";

                    if ($c->personalityResults) {

                        $r = $c->personalityResults;

                        if ($r->disc_json) {

                            $entry .= " | DISC: " . (
                                is_array($r->disc_json)
                                    ? json_encode($r->disc_json)
                                    : $r->disc_json
                            );
                        }
                    }

                    $lines[] = $entry;
                }
            }
        }

        $lines[] = "";
        $lines[] = "Responda usando o contexto acima.";

        return implode("\n", $lines);
    }
}