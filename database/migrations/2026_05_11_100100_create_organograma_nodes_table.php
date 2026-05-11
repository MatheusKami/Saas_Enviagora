<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O organograma é uma árvore — cada nó sabe quem é seu pai (parent_id)
        // Isso permite montar a hierarquia completa da empresa
        Schema::create('organograma_nodes', function (Blueprint $table) {
            $table->id();

            // Tudo filtrado por empresa — multi-tenancy básico
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->string('nome');
            $table->string('cargo')->nullable();
            $table->string('departamento')->nullable();
            $table->string('email')->nullable();

            // parent_id = null significa que é o topo da hierarquia (CEO, founder, etc.)
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('organograma_nodes')->nullOnDelete();

            // Vincula ao usuário do sistema se ele tiver conta (opcional)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Posição x/y no canvas drag-and-drop (para salvar o layout visual)
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);

            $table->timestamps();

            $table->index('company_id');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organograma_nodes');
    }
};
