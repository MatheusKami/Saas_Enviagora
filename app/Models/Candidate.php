<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Candidate extends Model
{
    protected $fillable = [
        'job_id',
        'company_id',
        'nome',
        'email',
        'linkedin_url',
        'cv_url',
        'doc_respostas_url',
        'entrevista_texto',
        'respostas_json',
        'test_link_token',
        'test_link_expires_at',
        'test_completed_at',
    ];

    protected $casts = [
        'respostas_json'       => 'array',
        'test_link_expires_at' => 'datetime',
        'test_completed_at'    => 'datetime',
    ];

    // Vaga a qual esse candidato pertence
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Empresa — atalho direto sem precisar passar pela vaga
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Resultados dos testes psicométricos do candidato
    public function personalityResults()
    {
        return $this->hasOne(PersonalityResult::class, 'subject_id')
                    ->where('subject_type', 'candidate');
    }

    // Relatório de match com a vaga
    public function matchReport()
    {
        return $this->hasOne(MatchReport::class);
    }

    // Link de testes gerado para esse candidato
    public function testLink()
    {
        return $this->hasOne(TestLink::class);
    }

    // Gera a URL pública do currículo no storage
    public function getCvUrlPublicaAttribute(): ?string
    {
        return $this->cv_url ? Storage::url($this->cv_url) : null;
    }

    // Verifica se esse candidato já completou os testes
    public function getTestesCompletosAttribute(): bool
    {
        return $this->test_completed_at !== null;
    }
}
