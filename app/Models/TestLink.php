<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TestLink extends Model
{
    protected $table = 'test_links';

    protected $fillable = [
        'company_id',
        'token',
        'candidate_id',
        'type',
        'employee_node_id',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function employeeNode()
    {
        return $this->belongsTo(OrganogramaNode::class, 'employee_node_id');
    }

    // Verifica se o link ainda está válido (não expirou e não foi usado)
    public function isValido(): bool
    {
        if ($this->completed_at) return false; // já foi usado
        if ($this->expires_at && $this->expires_at->isPast()) return false; // expirou
        return true;
    }
}
