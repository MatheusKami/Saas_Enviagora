<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganogramaNode extends Model
{
    protected $table = 'organograma_nodes';

    protected $fillable = [
        'company_id',
        'nome',
        'cargo',
        'departamento',
        'email',
        'parent_id',
        'user_id',
        'pos_x',
        'pos_y',
    ];

    // Empresa dona desse nó
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Nó pai na árvore hierárquica
    public function parent()
    {
        return $this->belongsTo(OrganogramaNode::class, 'parent_id');
    }

    // Filhos diretos na hierarquia (subordinados)
    public function children()
    {
        return $this->hasMany(OrganogramaNode::class, 'parent_id');
    }

    // Usuário do sistema vinculado a esse colaborador (se tiver conta)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Resultados de personalidade desse colaborador
    public function personalityResults()
    {
        return $this->hasOne(PersonalityResult::class, 'subject_id')
                    ->where('subject_type', 'employee');
    }

    // Vagas onde esse nó é o líder direto
    public function vagasComoLider()
    {
        return $this->hasMany(Job::class, 'lider_id');
    }
}
