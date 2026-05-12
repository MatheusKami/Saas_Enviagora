<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vagas', function (Blueprint $table) {

            // Campos que o briefing pede mas que não estavam na migration original

            if (!Schema::hasColumn('vagas', 'responsabilidades')) {
                $table->text('responsabilidades')->nullable();
            }

            if (!Schema::hasColumn('vagas', 'metas')) {
                $table->text('metas')->nullable(); // OKRs esperados
            }

            if (!Schema::hasColumn('vagas', 'motivo')) {
                $table->string('motivo')->nullable(); // motivo da abertura
            }

            if (!Schema::hasColumn('vagas', 'senioridade')) {
                $table->string('senioridade')->nullable(); // junior/pleno/senior/lider
            }

            if (!Schema::hasColumn('vagas', 'departamento')) {
                $table->string('departamento')->nullable();
            }

            if (!Schema::hasColumn('vagas', 'lider_id')) {
                // FK para o líder direto da vaga
                $table->foreignId('lider_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('vagas', 'jd_gerada')) {
                $table->longText('jd_gerada')->nullable();
            }

            if (!Schema::hasColumn('vagas', 'salary_min')) {
                $table->decimal('salary_min', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('vagas', 'salary_max')) {
                $table->decimal('salary_max', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('vagas', 'salary_texto')) {
                $table->string('salary_texto')->nullable();
            }

            if (!Schema::hasColumn('vagas', 'perfil_ideal_json')) {
                $table->json('perfil_ideal_json')->nullable();
            }

            if (!Schema::hasColumn('vagas', 'perguntas_triagem')) {
                $table->json('perguntas_triagem')->nullable();
            }

            if (!Schema::hasColumn('vagas', 'modo_criacao')) {
                $table->string('modo_criacao')->default('manual');
            }

        });
    }

    public function down(): void
    {
        Schema::table('vagas', function (Blueprint $table) {

            if (Schema::hasColumn('vagas', 'lider_id')) {
                $table->dropForeign(['lider_id']);
            }

            $columns = [
                'responsabilidades',
                'metas',
                'motivo',
                'senioridade',
                'departamento',
                'lider_id',
                'jd_gerada',
                'salary_min',
                'salary_max',
                'salary_texto',
                'perfil_ideal_json',
                'perguntas_triagem',
                'modo_criacao',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vagas', $column)) {
                    $table->dropColumn($column);
                }
            }

        });
    }
};