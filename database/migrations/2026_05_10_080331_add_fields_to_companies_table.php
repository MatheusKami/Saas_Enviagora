<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Adiciona apenas as colunas que ainda não existem
            if (!Schema::hasColumn('companies', 'cnpj')) {
                $table->string('cnpj')->nullable()->unique();
            }
            if (!Schema::hasColumn('companies', 'endereco_completo')) {
                $table->text('endereco_completo')->nullable();
            }
            if (!Schema::hasColumn('companies', 'url_empresa')) {
                $table->string('url_empresa')->nullable();
            }
            if (!Schema::hasColumn('companies', 'contexto_empresa')) {
                $table->text('contexto_empresa')->nullable();
            }
            if (!Schema::hasColumn('companies', 'perfil_ritmo')) {
                $table->string('perfil_ritmo')->nullable();
            }
            if (!Schema::hasColumn('companies', 'valores')) {
                $table->json('valores')->nullable();  // array de valores da empresa
            }
            if (!Schema::hasColumn('companies', 'logo_url')) {
                $table->string('logo_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'cnpj', 'endereco_completo', 'url_empresa',
                'contexto_empresa', 'perfil_ritmo', 'valores', 'logo_url',
            ]);
        });
    }
};