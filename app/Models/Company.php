<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'razao_social',
        'cnpj',
        'endereco_completo',
        'url_empresa',
        'contexto_empresa',
        'perfil_ritmo',
        'valores',
        'logo_url',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function vagas()
    {
        return $this->hasMany(Job::class);
    }
      protected $casts = [
        'valores' => 'array',
    ];
}