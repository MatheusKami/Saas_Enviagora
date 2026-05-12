<?php

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VagaController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\GroqController;
use App\Http\Controllers\WhiteLabelController;
use App\Http\Middleware\EnsureOnboardingComplete;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================================
// HOME
// ==========================================================
Route::get('/', function () {

    if (auth()->check()) {
        $company = auth()->user()->company;

        // Se não tem empresa ou não concluiu onboarding, manda pro onboarding
        if (! $company || ! $company->onboarding_completed) {
            $step = $company ? max(1, $company->onboarding_step) : 1;
            return redirect()->route('onboarding.step', $step);
        }

        return redirect()->route('dashboard');
    }

    return view('home');

})->name('home');

// ==========================================================
// ONBOARDING — fluxo inicial da empresa
// ==========================================================
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/onboarding', [OnboardingController::class, 'index'])
        ->name('onboarding');

    Route::get('/onboarding/step/{step}', [OnboardingController::class, 'show'])
        ->whereNumber('step')
        ->name('onboarding.step');

    Route::post('/onboarding/step/1', [OnboardingController::class, 'saveStep1'])
        ->name('onboarding.save.step1');

    Route::post('/onboarding/step/2', [OnboardingController::class, 'saveStep2'])
        ->name('onboarding.save.step2');

    Route::post('/onboarding/step/3', [OnboardingController::class, 'saveStep3'])
        ->name('onboarding.save.step3');

    Route::post('/onboarding/step/4', [OnboardingController::class, 'saveStep4'])
        ->name('onboarding.save.step4');

    // Save genérico
    Route::post('/onboarding/step/{step}', function (int $step, Request $request) {

        $controller = app(OnboardingController::class);

        return match ($step) {
            1 => $controller->saveStep1($request),
            2 => $controller->saveStep2($request),
            3 => $controller->saveStep3($request),
            4 => $controller->saveStep4($request),
            default => redirect()->route('onboarding.step', ['step' => 1]),
        };

    })->whereNumber('step')->name('onboarding.save');

});

// ==========================================================
// ÁREA LOGADA
// ==========================================================
Route::middleware([
    'auth',
    'verified',
    EnsureOnboardingComplete::class
])->group(function () {

    // ------------------------------------------------------
    // DASHBOARD
    // ------------------------------------------------------
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ------------------------------------------------------
    // CONFIGURAÇÕES EMPRESA
    // ------------------------------------------------------
    Route::get('/empresa/configuracoes', [DashboardController::class, 'configuracoes'])
        ->name('empresa.configuracoes');

    Route::put('/empresa/configuracoes', [DashboardController::class, 'atualizarConfiguracoes'])
        ->name('empresa.configuracoes.update');

    Route::post('/empresa/logo', [DashboardController::class, 'atualizarLogo'])
        ->name('empresa.logo.update');

    // ------------------------------------------------------
    // VAGAS
    // ------------------------------------------------------
    Route::prefix('vagas')->name('jobs.')->group(function () {

        Route::get('/', [VagaController::class, 'index'])
            ->name('index');

        Route::get('/criar', [VagaController::class, 'create'])
            ->name('create');

        Route::get('/criar-ia', [VagaController::class, 'createIa'])
            ->name('create-ia');

        Route::post('/', [VagaController::class, 'store'])
            ->name('store');

        Route::get('/{job}', [VagaController::class, 'show'])
            ->name('show');

        Route::get('/{job}/editar', [VagaController::class, 'edit'])
            ->name('edit');

        Route::put('/{job}', [VagaController::class, 'update'])
            ->name('update');

        Route::delete('/{job}', [VagaController::class, 'destroy'])
            ->name('destroy');

        Route::patch('/{job}/status', [VagaController::class, 'toggleStatus'])
            ->name('toggle-status');

        Route::get('/{job}/candidatos', [VagaController::class, 'candidatos'])
            ->name('candidatos');

    });

    // ------------------------------------------------------
    // CANDIDATOS
    // ------------------------------------------------------
    Route::prefix('candidatos')->name('candidates.')->group(function () {

        Route::get('/', [CandidateController::class, 'index'])
            ->name('index');

        Route::get('/{candidate}', [CandidateController::class, 'show'])
            ->name('show');

        Route::patch('/{candidate}/status', [CandidateController::class, 'updateStatus'])
            ->name('status');

    });

    // ------------------------------------------------------
    // MATCH IA
    // ------------------------------------------------------
    Route::prefix('match')->name('match.')->group(function () {

        Route::post('/{candidate}/{job}', [MatchController::class, 'analisar'])
            ->name('analisar');

        Route::get('/{candidate}/{job}', [MatchController::class, 'resultado'])
            ->name('resultado');

    });

    // ------------------------------------------------------
    // IA / GROQ
    // ------------------------------------------------------
    Route::prefix('ia')->name('ia.')->group(function () {

        Route::get('/chat', [GroqController::class, 'chat'])
            ->name('chat');

        Route::post('/chat', [GroqController::class, 'enviarMensagem'])
            ->name('chat.send');

        Route::post('/job-description', [GroqController::class, 'gerarJobDescription'])
            ->name('job-description');

        Route::post('/perguntas/{job}', [GroqController::class, 'gerarPerguntas'])
            ->name('perguntas');

    });

});

// ==========================================================
// WHITE LABEL
// ==========================================================
Route::prefix('portal/{subdomain}')
    ->name('whitelabel.')
    ->group(function () {

        Route::get('/', [WhiteLabelController::class, 'index'])
            ->name('index');

        Route::get('/vagas/{job}', [WhiteLabelController::class, 'vaga'])
            ->name('vaga');

        Route::get('/vagas/{job}/candidatar', [WhiteLabelController::class, 'formCandidatura'])
            ->name('candidatura.form');

        Route::post('/vagas/{job}/candidatar', [WhiteLabelController::class, 'candidatar'])
            ->name('candidatura.store');

        Route::get('/vagas/{job}/psicometrico', [WhiteLabelController::class, 'psicometrico'])
            ->name('psicometrico');

        Route::post('/vagas/{job}/psicometrico', [WhiteLabelController::class, 'salvarRespostas'])
            ->name('psicometrico.save');

    });

// ==========================================================
// AUTH ROUTES (BREEZE)
// ==========================================================
require __DIR__.'/auth.php';
