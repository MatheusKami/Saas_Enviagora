<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Models\Candidate;
use App\Services\GroqService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Controller do portal white-label — é o que o candidato vê
// Acessível via /portal/{subdomain} — em produção seria pelo subdomínio
class WhiteLabelController extends Controller
{
    public function __construct(private GroqService $groq) {}

    // =======================================================
    // GET /portal/{subdomain}
    // Página inicial do portal da empresa — lista de vagas abertas
    // =======================================================
    public function index(string $subdomain)
    {
        // Busco a empresa pelo subdomínio
        $company = Company::where('subdomain', $subdomain)
            ->where('onboarding_completed', true)
            ->firstOrFail();

        // Só mostro vagas ativas
        $vagas = $company->jobs()
            ->where('status', 'ativo')
            ->latest()
            ->paginate(12);

        return view('whitelabel.index', compact('company', 'vagas'));
    }

    // =======================================================
    // GET /portal/{subdomain}/vagas/{job}
    // Página de detalhes da vaga
    // =======================================================
    public function vaga(string $subdomain, Job $job)
    {
        $company = Company::where('subdomain', $subdomain)->firstOrFail();

        // Garanto que a vaga pertence a essa empresa
        abort_unless($job->company_id === $company->id, 404);

        return view('whitelabel.vaga', compact('company', 'job'));
    }

    // =======================================================
    // GET + POST /portal/{subdomain}/vagas/{job}/candidatar
    // Formulário de candidatura
    // =======================================================
    public function formCandidatura(string $subdomain, Job $job)
    {
        $company = Company::where('subdomain', $subdomain)->firstOrFail();
        abort_unless($job->company_id === $company->id && $job->status === 'ativo', 404);

        return view('whitelabel.candidatura', compact('company', 'job'));
    }

    public function candidatar(string $subdomain, Job $job, Request $request)
    {
        $company = Company::where('subdomain', $subdomain)->firstOrFail();
        abort_unless($job->company_id === $company->id && $job->status === 'ativo', 404);

        $request->validate([
            'nome'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'telefone'  => 'nullable|string|max:20',
            'linkedin'  => 'nullable|url|max:255',
            'resumo'    => 'required|string|min:100|max:3000',
            'curriculo' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB máx
        ]);

        // Salvo o currículo se enviado
        $curriculoPath = null;
        if ($request->hasFile('curriculo')) {
            $curriculoPath = $request->file('curriculo')->store('curriculos', 'public');
        }

        // Crio o candidato
        $candidate = Candidate::create([
            'job_id'        => $job->id,
            'company_id'    => $company->id,
            'nome'          => $request->nome,
            'email'         => $request->email,
            'telefone'      => $request->telefone,
            'linkedin'      => $request->linkedin,
            'resumo'        => $request->resumo,
            'curriculo_path'=> $curriculoPath,
            'status'        => 'novo',
            'token'         => Str::uuid(), // Token único pra o candidato acompanhar a candidatura
        ]);

        // Redireciono pro psicométrico se a vaga tiver perguntas configuradas
        if ($job->has_psicometrico) {
            return redirect()->route('whitelabel.psicometrico', [$subdomain, $job])
                ->with('candidate_id', $candidate->id)
                ->with('success', 'Candidatura registrada! Agora responda o questionário.');
        }

        return redirect()->route('whitelabel.vaga', [$subdomain, $job])
            ->with('success', '✅ Candidatura enviada com sucesso! Entraremos em contato em breve.');
    }

    // =======================================================
    // GET + POST /portal/{subdomain}/vagas/{job}/psicometrico
    // Teste psicométrico do candidato
    // =======================================================
    public function psicometrico(string $subdomain, Job $job)
    {
        $company = Company::where('subdomain', $subdomain)->firstOrFail();
        abort_unless($job->company_id === $company->id, 404);

        // Pego as perguntas da vaga (geradas pelo Groq ou cadastradas manualmente)
        $perguntas = $job->perguntas_psicometricas ?? [];

        return view('whitelabel.psicometrico', compact('company', 'job', 'perguntas'));
    }

    public function salvarRespostas(string $subdomain, Job $job, Request $request)
    {
        $company = Company::where('subdomain', $subdomain)->firstOrFail();

        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'respostas'    => 'required|array',
        ]);

        $candidate = Candidate::findOrFail($request->candidate_id);
        $candidate->update([
            'respostas_psicometricas' => $request->respostas,
            'psicometrico_respondido' => true,
        ]);

        return redirect()->route('whitelabel.vaga', [$subdomain, $job])
            ->with('success', '✅ Questionário enviado! Sua candidatura está completa.');
    }
}
