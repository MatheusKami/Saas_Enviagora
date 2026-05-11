<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'vagas'; // a tabela se chama vagas, não jobs

    protected $fillable = [
        'company_id',
        'titulo',
        'descricao',
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
        'status',
    ];

    protected $casts = [
        'perfil_ideal_json' => 'array',
        'perguntas_triagem' => 'array',
        'salary_min'        => 'decimal:2',
        'salary_max'        => 'decimal:2',
    ];

    // Empresa dona dessa vaga
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Líder direto no organograma
    public function leader()
    {
        return $this->belongsTo(OrganogramaNode::class, 'lider_id');
    }

    // Todos os candidatos dessa vaga
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    // Relatórios de match dessa vaga, ordenados por ranking
    public function matchReports()
    {
        return $this->hasMany(MatchReport::class)->orderBy('ranking_position');
    }

    // Mensagens do chat associadas a essa vaga
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}