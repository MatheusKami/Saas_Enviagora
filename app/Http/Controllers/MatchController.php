<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function analisar(Request $request, $candidate, $job)
    {
        return response()->json(['success' => true, 'message' => 'Match analysis stub - implement full logic in VagaController::gerarMatch']);
    }

    public function resultado($candidate, $job)
    {
        return view('match.resultado', compact('candidate', 'job'));
    }
}
