<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social');
            $table->string('cnpj')->unique()->nullable();
            $table->string('logo_url')->nullable();
            $table->text('contexto_empresa')->nullable();
            $table->string('perfil_ritmo')->nullable();
            $table->json('valores')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};