<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Crio a tabela completa de empresas aqui porque o onboarding tem 4 etapas
// e preciso guardar tudo: dados cadastrais, logo, organograma, contexto, etc.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // FK para o usuário dono da empresa (quem fez o cadastro)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // --- ETAPA 1: Dados cadastrais ---
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 18)->unique();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('website')->nullable();

            // Logo: salvo apenas o PATH relativo ao disco 'public'
            // Ex: "logos/abc123.png" — NÃO "storage/logos/abc123.png"
            // Isso facilita na hora de gerar a URL com Storage::url()
            $table->string('logo_path')->nullable();

            // Endereço (preenchido via ViaCEP automaticamente)
            $table->string('cep', 9)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();

            // Subdomínio do portal white-label do candidato
            // Ex: "acme" → acme.rhmatch.com.br
            $table->string('subdomain')->unique()->nullable();

            // --- ETAPA 2: Organograma ---
            // Salvo como JSON porque a estrutura pode variar muito
            $table->json('organograma')->nullable();

            // --- ETAPA 3: Colaboradores existentes ---
            // Quantos e em quais áreas — útil pro match psicométrico
            $table->json('colaboradores_por_area')->nullable();

            // --- ETAPA 4: Contexto e ritmo da empresa ---
            // Essas infos vão pro prompt do Groq quando gerar Job Description
            $table->text('cultura_empresa')->nullable();
            $table->string('ritmo_trabalho')->nullable(); // Ex: "startup acelerada", "corporativo estável"
            $table->string('modelo_trabalho')->nullable(); // presencial / remoto / híbrido
            $table->text('valores_empresa')->nullable();
            $table->text('diferenciais_empresa')->nullable();

            // Controle do onboarding (qual etapa o usuário parou)
            // 0 = não iniciou, 1-4 = em qual etapa, 5 = concluído
            $table->tinyInteger('onboarding_step')->default(0);
            $table->boolean('onboarding_completed')->default(false);

            $table->timestamps();
            $table->softDeletes(); // Nunca deleto empresa de verdade, só marco como deletada
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
