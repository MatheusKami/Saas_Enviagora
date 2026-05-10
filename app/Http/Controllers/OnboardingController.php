<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /onboarding
    // Exibe o wizard de cadastro da empresa.
    // Se o usuário já possui uma empresa vinculada, redireciona
    // direto para o dashboard.
    // ─────────────────────────────────────────────────────────
    public function index()
    {
        $user = Auth::user();

        if ($user->company_id) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.create');
    }

    // ─────────────────────────────────────────────────────────
    // POST /onboarding
    // Valida os dados, cria a empresa, faz upload do logo e
    // vincula a empresa ao usuário autenticado.
    // Retorna JSON (não redirect) porque o front usa fetch().
    // ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'razao_social'      => 'required|string|max:255',
            'cnpj'              => 'nullable|string|unique:companies,cnpj',
            'endereco_completo' => 'nullable|string',
            'url_empresa'       => 'nullable|url',
            'contexto_empresa'  => 'nullable|string',
            'perfil_ritmo'      => 'nullable|string',
            'valores'           => 'nullable|array',
            'logo'              => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
        ]);

        $company = Company::create([
            'razao_social'     => $validated['razao_social'],
            'cnpj'             => $validated['cnpj'] ?? null,
            'endereco_completo'=> $validated['endereco_completo'] ?? null,
            'url_empresa'      => $validated['url_empresa'] ?? null,
            'contexto_empresa' => $validated['contexto_empresa'] ?? null,
            'perfil_ritmo'     => $validated['perfil_ritmo'] ?? null,
            'valores'          => $validated['valores'] ?? null,
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $company->logo_url = Storage::url($path);
            $company->save();
        }

        $user = Auth::user();
        $user->company_id = $company->id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Empresa cadastrada com sucesso!'
        ]);
    }
}
