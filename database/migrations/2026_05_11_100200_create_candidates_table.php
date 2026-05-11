<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();

            // FK para a vaga — candidato sempre pertence a uma vaga
            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('vagas')->onDelete('cascade');

            // Repetimos o company_id aqui para facilitar filtros por empresa
            // sem precisar sempre fazer join com vagas
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('linkedin_url')->nullable();

            // Currículo e documentos enviados pelo RH (path no storage)
            $table->string('cv_url')->nullable();
            $table->string('doc_respostas_url')->nullable(); // PDF com respostas às perguntas

            // Transcrição de entrevista digitada ou colada pelo RH
            $table->longText('entrevista_texto')->nullable();

            // Respostas às perguntas de triagem da vaga (salvo como JSON)
            $table->json('respostas_json')->nullable();

            // Token único para o link de testes psicométricos
            // Quando null = ainda não foi gerado o link
            $table->string('test_link_token')->unique()->nullable();
            $table->timestamp('test_link_expires_at')->nullable();
            $table->timestamp('test_completed_at')->nullable(); // quando terminou os testes

            $table->timestamps();

            $table->index('company_id');
            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
