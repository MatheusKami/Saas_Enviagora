<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $company = $user->company ?? Company::find($user->company_id);

        if (!$company) {

            return redirect()
                ->route('onboarding')
                ->with('error', 'Complete o onboarding primeiro.');
        }

        return view('profile.edit', [
            'company' => $company,
            'user' => $user,
        ]);
    }

    public function updateDados(Request $request)
    {
        $user = Auth::user();
        $company = $user->company ?? Company::find($user->company_id);

        if (!$company) {
            return response()->json([
                'message' => 'Empresa não encontrada.'
            ], 404);
        }

        $validated = $request->validate([
            'razao_social'      => 'required|string|max:255',
            'cnpj'              => 'nullable|string|size:18|unique:companies,cnpj,' . $company->id,
            'url_empresa'       => 'nullable|url|max:255',
            'endereco_completo' => 'nullable|string|max:500',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
        ]);

        // Upload logo
        if ($request->hasFile('logo')) {

            // Remove antiga
            if ($company->logo_url) {

                $oldPath = str_replace('/storage/', '', $company->logo_url);

                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo')->store('companies/logos', 'public');

            // SALVA APENAS O PATH
            $validated['logo_url'] = $path;
        }

        $company->update($validated);

        return response()->json([
            'success' => true,
            'redirect' => route('editar_empresa'),
            'logo_url' => $company->logo_url
        ]);
    }
    public function updateCultura(Request $request)
    {
        $company = Auth::user()->company ?? Company::find(Auth::user()->company_id);

        $validated = $request->validate([
            'contexto_empresa' => 'nullable|string',
            'perfil_ritmo'    => 'nullable|in:dinamico,analitico,equilibrado,criativo',
            'valores'          => 'nullable|array',
        ]);

        $company->update($validated);

        return response()->json([
            'success' => true,
            'redirect' => route('editar_empresa')
        ]);
    }
}