<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Job; // ajuste para o nome correto do seu model de vagas

class ChatController extends Controller
{
    /**
     * Exibe a view do chat.
     */
    public function index(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->company_id;           // ou empresa_id se você usar esse campo
        $jobId     = $request->query('job_id');

        // Busca a vaga (com relacionamentos necessários)
        $job = $jobId 
            ? \App\Models\Job::with(['candidates.personalityResults', 'leader'])
                ->where('id', $jobId)
                ->where('company_id', $companyId)
                ->first()
            : null;

        // Histórico de mensagens
        $history = \App\Models\ChatMessage::where('company_id', $companyId)
            ->when($jobId, fn($q) => $q->where('job_id', $jobId))
            ->when(!$jobId, fn($q) => $q->whereNull('job_id'))
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        return view('chat', compact('history', 'job'));
    }

    /**
     * Recebe a mensagem do usuário e retorna a resposta da IA (Groq).
     */
    public function send(Request $request)
    {
        $request->validate([
            'message'  => 'required|string|max:2000',
            'history'  => 'nullable|array',
        ]);

        $user    = Auth::user();
        $message = $request->input('message');
        $history = $request->input('history', []);

        // Garante que o histórico é um array limpo
        $history = $this->normalizeHistory($history);

        // Monta o system prompt
        $systemPrompt = $this->buildSystemPrompt($user);

        // Monta as mensagens para a API
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $message]]
        );

        // Chama a API do Groq
        $response = $this->callGroq($messages);

        if ($response === null) {
            return response()->json([
                'error' => 'Não foi possível obter resposta da IA. Tente novamente.',
            ], 500);
        }

        return response()->json([
            'response' => $response,
        ]);
    }

    /**
     * Limpa o histórico da conversa (chamado via rota).
     */
    public function clear(Request $request)
    {
        // O histórico é mantido no frontend (Alpine.js).
        // Este endpoint existe para compatibilidade / logs futuros.
        return response()->json(['status' => 'cleared']);
    }

    /**
     * Sanitiza / normaliza o histórico recebido do frontend.
     * Garante que cada item tem role (string) e content (string).
     */
    public function sanitizeHistory(array $history): array
    {
        return $this->normalizeHistory($history);
    }

    // -------------------------------------------------------------------------
    // Métodos privados
    // -------------------------------------------------------------------------

    /**
     * Constrói o system prompt com base nos dados do usuário/empresa.
     * Todos os valores são forçados para string para evitar "Array to string conversion".
     */
    private function buildSystemPrompt(?User $user): string
    {
        $nomeEmpresa  = $this->toString($user?->company?->name ?? $user?->company_name ?? 'sua empresa');
        $nomeUsuario  = $this->toString($user?->name ?? 'usuário');
        $cargoUsuario = $this->toString($user?->role ?? $user?->cargo ?? 'colaborador');

        // Se a empresa tiver um prompt personalizado salvo no banco, usa ele como base
        $promptPersonalizado = $this->toString($user?->company?->system_prompt ?? '');

        if ($promptPersonalizado !== '') {
            return $promptPersonalizado;
        }

        return <<<PROMPT
Você é um assistente de RH inteligente da empresa {$nomeEmpresa}.
Você está conversando com {$nomeUsuario}, que ocupa o cargo de {$cargoUsuario}.

Seu objetivo é:
- Ajudar com dúvidas sobre processos seletivos e vagas abertas.
- Auxiliar na triagem e análise de candidatos.
- Responder perguntas sobre a empresa e seus processos internos.
- Ser sempre profissional, claro e objetivo.

Responda sempre em português do Brasil.
PROMPT;
    }

    /**
     * Chama a API do Groq e retorna o texto da resposta.
     */
    private function callGroq(array $messages): ?string
    {
        $apiKey = config('services.groq.api_key') ?? env('GROQ_API_KEY');
        $model  = config('services.groq.model', 'llama3-8b-8192');

        if (!$apiKey) {
            \Log::error('GROQ_API_KEY não configurada.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.7,
                'max_tokens'  => 1024,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            \Log::error('Groq API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;

        } catch (\Throwable $e) {
            \Log::error('Groq API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Normaliza o histórico garantindo que role e content sejam sempre strings.
     */
    private function normalizeHistory(array $history): array
    {
        $clean = [];
        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }
            $role    = $this->toString($item['role'] ?? 'user');
            $content = $this->toString($item['content'] ?? '');

            // Aceita apenas roles válidos da OpenAI/Groq
            if (!in_array($role, ['user', 'assistant', 'system'])) {
                $role = 'user';
            }

            if ($content !== '') {
                $clean[] = ['role' => $role, 'content' => $content];
            }
        }
        return $clean;
    }

    /**
     * Converte qualquer valor para string de forma segura.
     * Resolve o "Array to string conversion" de uma vez por todas.
     */
    private function toString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn($v) => $this->toString($v), $value));
        }
        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : json_encode($value);
        }
        if (is_null($value) || is_bool($value)) {
            return '';
        }
        return (string) $value;
    }
}
