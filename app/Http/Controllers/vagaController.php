<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VagaController extends Controller
{
    public function index()
    {
        $vagas = \App\Models\Job::where('company_id', Auth::user()->company_id)
                ->latest()
                ->get();

        return view('vagas.index', compact('vagas'));
    }

    public function nova_vaga()
    {
        return view('vagas.create-manual');
    }

    public function nova_ia()
    {
        return view('vagas.create-ia');
    }
}