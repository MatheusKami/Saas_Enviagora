<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalityResult extends Model
{
    protected $table = 'personality_results';

    protected $fillable = [
        'company_id',
        'subject_id',
        'subject_type',
        'disc_json',
        'enneagram_json',
        'mbti_json',
        'completed',
    ];

    protected $casts = [
        'disc_json'       => 'array',
        'enneagram_json'  => 'array',
        'mbti_json'       => 'array',
        'completed'       => 'boolean',
    ];

    // Candidato dono desse resultado (quando subject_type = candidate)
    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'subject_id');
    }

    // Colaborador dono desse resultado (quando subject_type = employee)
    public function employee()
    {
        return $this->belongsTo(OrganogramaNode::class, 'subject_id');
    }

    // Retorna o perfil DISC formatado ex: "D (75%)"
    public function getDiscPerfilAttribute(): string
    {
        if (!$this->disc_json) return 'Não realizado';
        $d = $this->disc_json;
        $perfil = $d['perfil'] ?? '?';
        $pct    = $d[$perfil] ?? 0;
        return "{$perfil} ({$pct}%)";
    }

    // Retorna o tipo MBTI ex: "INTJ"
    public function getMbtiTipoAttribute(): string
    {
        return $this->mbti_json['tipo'] ?? 'Não realizado';
    }

    // Retorna o tipo do eneagrama ex: "3w4"
    public function getEnneagramTipoAttribute(): string
    {
        if (!$this->enneagram_json) return 'Não realizado';
        $tipo = $this->enneagram_json['tipo'] ?? '?';
        $asa  = $this->enneagram_json['asa'] ?? '';
        return $asa ? "{$tipo}w{$asa}" : (string)$tipo;
    }
}
