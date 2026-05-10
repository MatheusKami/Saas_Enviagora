<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vagas', function (Blueprint $table) {
            $table->id();
            
            // Forma mais segura de criar foreign key
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');

            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('status')->default('ativa');
            $table->timestamps();

            // Índice para melhorar performance
            $table->index('company_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vagas');
    }
};