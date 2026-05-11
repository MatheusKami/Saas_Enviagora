<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VagaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\EmpresaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// ─────────────────────────────────────────────────────────────
// Página inicial
// ─────────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('home');
});

// ─────────────────────────────────────────────────────────────
// Rotas protegidas — exigem login e e-mail verificado
// ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────
    Route::get('/dashboard', function () {
        $company = Auth::user()->company_id
            ? \App\Models\Company::find(Auth::user()->company_id)
            : null;
        return view('dashboard', compact('company'));
    })->name('dashboard');

    // ── Perfil do usuário ──────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Configurações (alias para profile/edit) ────────────────
    Route::get('/configurar', function () {
        $company = auth()->user()->company;   // ou auth()->user()->company()->first()

        // Se o usuário pode não ter empresa, use:
        // $company = auth()->user()->company ?? null;

        return view('profile.edit', compact('company'));
    })->name('configuracoes.index');
    // ── Vagas ─────────────────────────────────────────────────
    Route::prefix('vagas')->name('vagas.')->group(function () {
        Route::get('/',            [VagaController::class, 'index'])->name('index');
        Route::get('/nova-ia',     [VagaController::class, 'nova_ia'])->name('create-ia');
        Route::get('/nova-manual', [VagaController::class, 'nova_vaga'])->name('create-manual');
    });

    // ── Chat — Assistente de RH ───────────────────────────────
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/',       [ChatController::class, 'index'])->name('index');
        Route::post('/send',  [ChatController::class, 'send'])->name('send');
        Route::post('/clear', [ChatController::class, 'clear'])->name('clear');
    });

    // ── Onboarding — cadastro inicial da empresa ──────────────
    Route::get('/onboarding',  [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    // ── Empresa — edição dos dados ────────────────────────────
    // GET  /editar_empresa           → exibe o formulário completo
    // PUT  /editar_empresa           → salva dados cadastrais + logo
    // PUT  /editar_empresa/cultura   → salva contexto, ritmo e valores
    Route::get('/editar_empresa',         [EmpresaController::class, 'edit'])->name('editar_empresa');
    Route::put('/editar_empresa',         [EmpresaController::class, 'updateDados'])->name('editar_empresa.dados');
    Route::put('/editar_empresa/cultura', [EmpresaController::class, 'updateCultura'])->name('editar_empresa.cultura');
});

// ─────────────────────────────────────────────────────────────
// Auth — rotas geradas pelo Breeze
// ─────────────────────────────────────────────────────────────
require __DIR__.'/auth.php';
