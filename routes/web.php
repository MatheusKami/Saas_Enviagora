<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VagaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\EmpresaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('home');
});

// Rotas protegidas
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
        $user = auth()->user();
        $company = $user->company ?? ($user->company_id ? \App\Models\Company::find($user->company_id) : null);

        return view('profile.edit', compact('user', 'company'));
    })->name('configuracoes.index');

    // Vagas
    Route::prefix('vagas')->name('vagas.')->group(function () {
        Route::get('/',            [VagaController::class, 'index'])->name('index');
        Route::get('/nova-ia',     [VagaController::class, 'nova_ia'])->name('create-ia');
        Route::get('/nova-manual', [VagaController::class, 'nova_vaga'])->name('create-manual');
    });

    // Chat
    Route::get('/chat',         [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send',   [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/clear',  [ChatController::class, 'clear'])->name('chat.clear');

    // Onboarding
    Route::get('/onboarding',  [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    // Empresa (edição completa + logo)
    Route::get('/editar_empresa',         [EmpresaController::class, 'edit'])->name('editar_empresa');
    Route::put('/editar_empresa',         [EmpresaController::class, 'updateDados'])->name('editar_empresa.dados');
    Route::put('/editar_empresa/cultura', [EmpresaController::class, 'updateCultura'])->name('editar_empresa.cultura');
});

require __DIR__.'/auth.php';