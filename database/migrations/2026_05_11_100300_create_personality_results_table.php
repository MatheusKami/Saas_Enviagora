<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resultados dos 3 testes psicométricos — serve tanto para candidatos quanto colaboradores
        Schema::create('personality_results', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // subject_id = id do candidato OU do nó do organograma (colaborador)
            // subject_type = "candidate" ou "employee"
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_type'); // candidate | employee

            // Resultados de cada teste em JSON
            // DISC: { "perfil": "D", "D": 75, "I": 20, "S": 10, "C": 40 }
            $table->json('disc_json')->nullable();

            // Eneagrama: { "tipo": 3, "asa": "3w4", "descricao": "..." }
            $table->json('enneagram_json')->nullable();

            // 16 Personalidades / MBTI: { "tipo": "INTJ", "E": 20, "I": 80, "N": 70, "S": 30, ... }
            $table->json('mbti_json')->nullable();

            // Flag para saber se o candidato completou todos os 3 testes
            $table->boolean('completed')->default(false);

            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personality_results');
    }
};
