<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'vagas';   // ← importante!

    protected $fillable = [
        'company_id',
        'titulo',
        'descricao',
        'status',
        'responsabilidades',
        'jd_gerada',
        'perfil_ideal_json',
        // adicione outros campos conforme for expandindo
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // futuramente: candidates, leader, etc.
}