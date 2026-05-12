<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Esse controller gerencia todo o fluxo de onboarding da empresa
// 4 etapas: dados cadastrais → organograma → colaboradores → contexto
class OnboardingController extends Controller
{
    // =======================================================
    // GET /onboarding
    // Redireciona pra etapa certa baseado no progresso atual
    // =======================================================
    public function index()
    {
        $company = $this->getOrCreateCompany();

        // Se já concluiu o onboarding, manda pro dashboard
        if ($company->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        // Redireciona pra etapa que o usuário parou
        $step = max(1, $company->onboarding_step);
        return redirect()->route('onboarding.step', $step);
    }

    // =======================================================
    // GET /onboarding/{step}
    // Mostra a view da etapa correspondente
    // =======================================================
    public function show(int $step)
    {
        // Valido o range de etapas aqui pra não deixar pular etapas
        if ($step < 1 || $step > 4) {
            return redirect()->route('onboarding.step', 1);
        }

        $company = $this->getOrCreateCompany();

        // Não deixo pular etapas — se tentar ir pra etapa 3 sem completar 2, volta
        if ($step > ($company->onboarding_step + 1) && $company->onboarding_step > 0) {
            return redirect()->route('onboarding.step', $company->onboarding_step);
        }

        return view('onboarding.step' . $step, compact('company', 'step'));
    }

    // =======================================================
    // POST /onboarding/step/1
    // ETAPA 1: Dados cadastrais + UPLOAD DA LOGO (bug #1 corrigido aqui)
    // =======================================================
    public function saveStep1(Request $request)
    {
        $request->validate([
            'razao_social'  => 'required|string|max:255',
            'cnpj'          => 'required|string|max:18',
            'nome_fantasia' => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'telefone'      => 'nullable|string|max:20',
            'website'       => 'nullable|url|max:255',
            'cep'           => 'nullable|string|max:9',
            'logradouro'    => 'nullable|string|max:255',
            'numero'        => 'nullable|string|max:20',
            'complemento'   => 'nullable|string|max:255',
            'bairro'        => 'nullable|string|max:255',
            'cidade'        => 'nullable|string|max:255',
            'estado'        => 'nullable|string|max:2',
            'subdomain'     => 'nullable|string|max:50|alpha_dash|unique:companies,subdomain,' . Auth::user()->company?->id,
            // Logo: aceito jpg, png e webp, máximo 2MB
            'logo'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'razao_social.required' => 'Razão social é obrigatória.',
            'cnpj.required'         => 'CNPJ é obrigatório.',
            'logo.image'            => 'O arquivo precisa ser uma imagem.',
            'logo.max'              => 'A logo pode ter no máximo 2MB.',
            'subdomain.alpha_dash'  => 'O subdomínio só pode ter letras, números e hífens.',
            'subdomain.unique'      => 'Esse subdomínio já está em uso.',
        ]);

        $company = $this->getOrCreateCompany();

        // =====================================================
        // AQUI É O BUG #1 CORRIGIDO: Upload da logo
        // =====================================================
        // ERRADO (como provavelmente estava antes):
        //   $path = $request->file('logo')->store('logos');  // <- usa disco 'local', não acessível via web!
        //
        // CERTO:
        //   Uso o disco 'public' que fica em storage/app/public/
        //   Com o symlink criado (storage:link), fica acessível em public/storage/
        $logoPath = $company->logo_path; // Mantém a logo atual se não enviar uma nova

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            // Deleto a logo antiga antes de salvar a nova (economiza espaço)
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            // Gero um nome único pra evitar colisões e problemas de cache do browser
            $filename = Str::uuid() . '.' . $request->file('logo')->getClientOriginalExtension();

            // Salvo em storage/app/public/logos/ usando o disco 'public'
            // O Storage::url() vai conseguir gerar a URL correta depois
            $logoPath = $request->file('logo')->storeAs('logos', $filename, 'public');

            // $logoPath agora é "logos/uuid.png" — só o caminho relativo, não a URL completa
            // Isso é o que salvo no banco. A URL completa gero com Storage::url($logoPath) quando precisar
        }

        // Monto os dados pra salvar — separo pra ficar mais fácil de debugar
        $data = array_merge(
            $request->only([
                'razao_social', 'nome_fantasia', 'cnpj', 'email',
                'telefone', 'website', 'cep', 'logradouro', 'numero',
                'complemento', 'bairro', 'cidade', 'estado', 'subdomain',
            ]),
            ['logo_path' => $logoPath]
        );

        // Avanço pra etapa 2 se ainda estiver na 1 (não volto se já passou)
        if ($company->onboarding_step <= 1) {
            $data['onboarding_step'] = 1;
        }

        $company->update($data);

        return redirect()
            ->route('onboarding.step', 2)
            ->with('success', 'Dados cadastrais salvos! Agora vamos montar o organograma.');
    }

    // =======================================================
    // POST /onboarding/step/2
    // ETAPA 2: Organograma da empresa
    // =======================================================
    public function saveStep2(Request $request)
    {
        $request->validate([
            // Recebo o organograma como JSON string do drag-and-drop no front
            'organograma' => 'required|string',
        ], [
            'organograma.required' => 'Adicione pelo menos um departamento no organograma.',
        ]);

        // Valido se é um JSON válido antes de salvar
        $organograma = json_decode($request->organograma, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($organograma)) {
            return back()->withErrors(['organograma' => 'Estrutura do organograma inválida.']);
        }

        $company = $this->getOrCreateCompany();
        $company->update([
            'organograma'     => $organograma,
            'onboarding_step' => max($company->onboarding_step, 2),
        ]);

        return redirect()
            ->route('onboarding.step', 3)
            ->with('success', 'Organograma salvo! Agora me fala dos colaboradores atuais.');
    }

    // =======================================================
    // POST /onboarding/step/3
    // ETAPA 3: Colaboradores existentes por área
    // =======================================================
    public function saveStep3(Request $request)
    {
        $request->validate([
            // Array de áreas com quantidade de colaboradores
            'areas'              => 'required|array|min:1',
            'areas.*.nome'       => 'required|string|max:100',
            'areas.*.quantidade' => 'required|integer|min:0',
        ], [
            'areas.required'              => 'Informe pelo menos uma área.',
            'areas.*.nome.required'       => 'Nome da área é obrigatório.',
            'areas.*.quantidade.required' => 'Quantidade de colaboradores é obrigatória.',
        ]);

        // Salvo como JSON no banco — o cast do model converte automaticamente
        $colaboradoresPorArea = collect($request->areas)
            ->filter(fn($area) => ! empty($area['nome'])) // Remove linhas vazias
            ->values()
            ->toArray();

        $company = $this->getOrCreateCompany();
        $company->update([
            'colaboradores_por_area' => $colaboradoresPorArea,
            'onboarding_step'        => max($company->onboarding_step, 3),
        ]);

        return redirect()
            ->route('onboarding.step', 4)
            ->with('success', 'Colaboradores registrados! Última etapa: contexto da empresa.');
    }

    // =======================================================
    // POST /onboarding/step/4
    // ETAPA 4: Contexto e ritmo da empresa (vai pro prompt do Groq)
    // =======================================================
    public function saveStep4(Request $request)
    {
        $request->validate([
            'cultura_empresa'      => 'required|string|min:50|max:2000',
            'ritmo_trabalho'       => 'required|string|max:100',
            'modelo_trabalho'      => 'required|in:presencial,remoto,hibrido',
            'valores_empresa'      => 'nullable|string|max:1000',
            'diferenciais_empresa' => 'nullable|string|max:1000',
        ], [
            'cultura_empresa.required' => 'Descreva a cultura da empresa.',
            'cultura_empresa.min'      => 'Descreva com pelo menos 50 caracteres.',
            'ritmo_trabalho.required'  => 'Selecione o ritmo de trabalho.',
            'modelo_trabalho.required' => 'Selecione o modelo de trabalho.',
        ]);

        $company = $this->getOrCreateCompany();
        $company->update([
            'cultura_empresa'      => $request->cultura_empresa,
            'ritmo_trabalho'       => $request->ritmo_trabalho,
            'modelo_trabalho'      => $request->modelo_trabalho,
            'valores_empresa'      => $request->valores_empresa,
            'diferenciais_empresa' => $request->diferenciais_empresa,
            'onboarding_step'      => 4,
            'onboarding_completed' => true, // Marca como concluído!
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', '🎉 Parabéns! Configuração concluída. Bem-vindo ao RHMatch!');
    }

    // =======================================================
    // Helper: Pega a empresa do usuário logado ou cria uma nova
    // =======================================================
    // FIX DEFINITIVO: Sempre inclui 'user_id' explicitamente na criação
    // para evitar o erro 1364 'Field user_id doesn't have a default value'
    // =======================================================
    private function getOrCreateCompany(): Company
    {
        $user = Auth::user();

        return Company::firstOrCreate(
            ['user_id' => $user->id],
            [
                'user_id'              => $user->id,           // ← EXPLÍCITO (garante que vai no INSERT)
                'razao_social'         => 'Empresa Temporária ' . $user->id,
                'cnpj'                 => 'TEMP-' . $user->id,
                'onboarding_step'      => 0,
                'onboarding_completed' => false,
            ]
        );
    }
}
