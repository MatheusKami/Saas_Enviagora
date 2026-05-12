<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Esse middleware garante que o usuário não pule o onboarding
// Aplico nas rotas do dashboard, vagas, candidatos, etc.
class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // IMPORTANTE: query direta ao invés de $user->company para evitar
        // cache de relacionamento do Eloquent que pode retornar null incorretamente
        $company = Company::where('user_id', $user->id)->first();

        // Se não tem empresa cadastrada, manda pro início do onboarding (etapa 1)
        if (! $company) {
            return redirect()->route('onboarding.step', 1)
                ->with('info', 'Complete a configuração da sua empresa para continuar.');
        }

        // Se tem empresa mas o onboarding não foi concluído, manda pra etapa certa
        if (! $company->onboarding_completed) {
            $step = max(1, $company->onboarding_step);
            return redirect()->route('onboarding.step', $step)
                ->with('info', 'Finalize a configuração antes de acessar essa área.');
        }

        return $next($request);
    }
}
