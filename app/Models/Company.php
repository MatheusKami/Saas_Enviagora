<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

// Model principal da empresa — tudo que o RHMatch precisa sobre o cliente
class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'email',
        'telefone',
        'website',
        'logo_path',       // Salvo o path, não a URL completa — mais seguro e portável
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'subdomain',
        'organograma',
        'colaboradores_por_area',
        'cultura_empresa',
        'ritmo_trabalho',
        'modelo_trabalho',
        'valores_empresa',
        'diferenciais_empresa',
        'onboarding_step',
        'onboarding_completed',
    ];

    protected $casts = [
        // Converte JSON automaticamente pra array PHP — sem precisar json_decode manual
        'organograma'          => 'array',
        'colaboradores_por_area' => 'array',
        'onboarding_completed' => 'boolean',
    ];

    // =======================================================
    // ACCESSOR: logo_url
    // Uso assim no blade: {{ $company->logo_url }}
    // Retorna a URL pública completa ou null se não tiver logo
    // =======================================================
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        // Storage::url() usa o disco 'public' e gera: /storage/logos/xyz.png
        // Funciona perfeitamente DEPOIS do php artisan storage:link
        return Storage::disk('public')->url($this->logo_path);
    }

    // =======================================================
    // ACCESSOR: display_name
    // Prefere o nome fantasia, cai pro razão social se não tiver
    // =======================================================
    public function getDisplayNameAttribute(): string
    {
        return $this->nome_fantasia ?: $this->razao_social;
    }

    // =======================================================
    // SCOPE: onboarding concluído
    // Uso: Company::completed()->get()
    // =======================================================
    public function scopeCompleted($query)
    {
        return $query->where('onboarding_completed', true);
    }

    // =======================================================
    // Relações
    // =======================================================

    // Dono da empresa (usuário que fez o cadastro)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Vagas abertas pela empresa
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    // Candidatos que se aplicaram pras vagas dessa empresa (via jobs)
    public function candidates()
    {
        return $this->hasManyThrough(Candidate::class, Job::class);
    }
}
