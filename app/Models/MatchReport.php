<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchReport extends Model
{
    protected $table = 'match_reports';

    protected $fillable = [
        'job_id',
        'candidate_id',
        'ranking_position',
        'match_score',
        'relatorio_json',
        'status',
    ];

    protected $casts = [
        'relatorio_json' => 'array',
    ];

    // Vaga que gerou esse relatório
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Candidato analisado
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    // Atalho para saber se o processamento terminou
    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    // Retorna a justificativa textual do match (salva dentro do JSON)
    public function getJustificativaAttribute(): string
    {
        return $this->relatorio_json['justificativa'] ?? '';
    }
}
