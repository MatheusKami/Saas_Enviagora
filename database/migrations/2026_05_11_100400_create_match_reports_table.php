<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relatório de match entre candidato e vaga gerado pela IA
        Schema::create('match_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('vagas')->onDelete('cascade');

            $table->unsignedBigInteger('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');

            // Posição no ranking geral (1 = mais indicado)
            $table->integer('ranking_position')->nullable();

            // Score de 0 a 100
            $table->integer('match_score')->nullable();

            // Todo o relatório detalhado que a IA gerou em JSON
            // Inclui: pontos fortes, pontos de atenção, perguntas, case, plano de desenvolvimento
            $table->json('relatorio_json')->nullable();

            // Status do processamento
            $table->string('status')->default('pending'); // pending | processing | done | error

            $table->timestamps();

            $table->index('job_id');
            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_reports');
    }
};
