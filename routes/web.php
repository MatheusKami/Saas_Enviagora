<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VagaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\OrganogramaController;
use App\Http\Controllers\TesteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Página inicial (marketing)
Route::get('/', function () {
    return view('home');
});

// Portal white-label de testes psicométricos — acesso público via token, sem login
Route::prefix('teste')->name('teste.')->group(function () {
    Route::get('/{token}',            [TesteController::class, 'show'])->name('show');
    Route::post('/{token}/disc',      [TesteController::class, 'salvarDisc'])->name('disc');
    Route::post('/{token}/enneagram', [TesteController::class, 'salvarEnneagram'])->name('enneagram');
    Route::post('/{token}/mbti',      [TesteController::class, 'salvarMbti'])->name('mbti');
    Route::get('/{token}/resultado',  [TesteController::class, 'resultado'])->name('resultado');
});

// Rotas protegidas — precisa estar logado e com e-mail verificado
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        $company = Auth::user()->company_id
            ? \App\Models\Company::find(Auth::user()->company_id)
            : null;
        return view('dashboard', compact('company'));
    })->name('dashboard');

    // Perfil do usuário
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Configurações (edição de usuário + empresa)
    Route::get('/configurar', function () {
        $user    = auth()->user();
        $company = $user->company ?? ($user->company_id ? \App\Models\Company::find($user->company_id) : null);
        return view('profile.edit', compact('user', 'company'));
    })->name('configuracoes.index');

    // Vagas
    Route::prefix('vagas')->name('vagas.')->group(function () {
        Route::get('/',        [VagaController::class, 'index'])->name('index');
        Route::get('/nova-ia',     [VagaController::class, 'nova_ia'])->name('create-ia');
        Route::get('/nova-manual', [VagaController::class, 'nova_vaga'])->name('create-manual');
        Route::get('/{id}',    [VagaController::class, 'show'])->name('show');
        Route::post('/',       [VagaController::class, 'store'])->name('store');
        Route::post('/gerar-jd', [VagaController::class, 'gerarJD'])->name('gerar-jd');
        Route::post('/{id}/candidatos', [VagaController::class, 'adicionarCandidato'])->name('candidatos.store');
        Route::post('/{id}/match', [VagaController::class, 'gerarMatch'])->name('match');
        Route::post('/{vagaId}/candidatos/{candidateId}/link-teste',
                    [VagaController::class, 'gerarLinkTeste'])->name('candidatos.link-teste');
    });

    // Organograma
    Route::prefix('organograma')->name('organograma.')->group(function () {
        Route::get('/',           [OrganogramaController::class, 'index'])->name('index');
        Route::post('/nodes',     [OrganogramaController::class, 'store'])->name('store');
        Route::put('/nodes/{id}', [OrganogramaController::class, 'update'])->name('update');
        Route::delete('/nodes/{id}', [OrganogramaController::class, 'destroy'])->name('destroy');
        Route::get('/data',       [OrganogramaController::class, 'data'])->name('data');
    });

    // Chat assistente de RH
    Route::get('/chat',        [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send',  [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/clear', [ChatController::class, 'clear'])->name('chat.clear');

    // Onboarding
    Route::get('/onboarding',  [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    // Empresa (edição após onboarding)
    Route::get('/editar_empresa',         [EmpresaController::class, 'edit'])->name('editar_empresa');
    Route::put('/editar_empresa',         [EmpresaController::class, 'updateDados'])->name('editar_empresa.dados');
    Route::put('/editar_empresa/cultura', [EmpresaController::class, 'updateCultura'])->name('editar_empresa.cultura');
});

require __DIR__.'/auth.php';
