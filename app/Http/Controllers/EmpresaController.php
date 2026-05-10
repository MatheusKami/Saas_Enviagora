<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;

class EmpresaController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /editar_empresa
    // Exibe o formulário com os dados atuais da empresa.
    // Redireciona para o onboarding se o usuário não tem empresa.
    // ─────────────────────────────────────────────────────────
    public function edit()
    {
        $user    = Auth::user();
        $company = Company::find($user->company_id);

        if (!$company) {
            return redirect()->route('onboarding')
                             ->with('info', 'Cadastre sua empresa primeiro.');
        }

        return view('empresa.edit', compact('company'));
    }

    // ─────────────────────────────────────────────────────────
    // PUT /editar_empresa
    // Atualiza os dados cadastrais (razão social, cnpj,
    // endereço, site e logo).
    // ─────────────────────────────────────────────────────────
    public function updateDados(Request $request)
    {
        $user    = Auth::user();
        $company = Company::findOrFail($user->company_id);

        $request->validate([
            'razao_social'      => 'required|string|max:255',
            'cnpj'              => 'nullable|string|unique:companies,cnpj,' . $company->id,
            'endereco_completo' => 'nullable|string',
            'url_empresa'       => 'nullable|url',
            'logo'              => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
        ]);

        $company->fill([
            'razao_social'      => $request->razao_social,
            'cnpj'              => $request->cnpj,
            'endereco_completo' => $request->endereco_completo,
            'url_empresa'       => $request->url_empresa,
        ]);

        // Substitui o logo antigo se um novo foi enviado
        if ($request->hasFile('logo')) {
            if ($company->logo_url) {
                Storage::delete(str_replace('/storage/', 'public/', $company->logo_url));
            }
            $path = $request->file('logo')->store('logos', 'public');
            $company->logo_url = Storage::url($path);
        }

        $company->save();

        return response()->json([
            'success'  => true,
            'redirect' => route('editar_empresa'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /editar_empresa/cultura
    // Atualiza o perfil/ritmo, contexto e valores da empresa.
    // ─────────────────────────────────────────────────────────
    public function updateCultura(Request $request)
    {
        $user    = Auth::user();
        $company = Company::findOrFail($user->company_id);

        $request->validate([
            'contexto_empresa' => 'nullable|string',
            'perfil_ritmo'     => 'nullable|string',
            'valores'          => 'nullable|array',
        ]);

        $company->fill([
            'contexto_empresa' => $request->contexto_empresa,
            'perfil_ritmo'     => $request->perfil_ritmo,
            'valores'          => $request->valores,
        ]);

        $company->save();

        return response()->json([
            'success'  => true,
            'redirect' => route('editar_empresa'),
        ]);
    }
}
