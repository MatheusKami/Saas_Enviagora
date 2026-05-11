<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada link de teste é único e de uso único
        // Serve tanto para candidatos externos quanto para colaboradores internos
        Schema::create('test_links', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Token único que vai na URL: /teste/{token}
            $table->string('token')->unique();

            // A quem pertence o link
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->foreign('candidate_id')->references('id')->on('candidates')->nullOnDelete();

            // type = "candidate" ou "employee" (para colaboradores do organograma)
            $table->string('type')->default('candidate');

            // ID do colaborador do organograma se for "employee"
            $table->unsignedBigInteger('employee_node_id')->nullable();

            // Prazo de validade configurável pelo RH
            $table->timestamp('expires_at')->nullable();

            // Preenchido quando o candidato conclui os 3 testes
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('token');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_links');
    }
};
