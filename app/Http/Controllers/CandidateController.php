<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function index()
    {
        return view('candidates.index'); // TODO: implement
    }

    public function show($candidate)
    {
        return view('candidates.show', compact('candidate'));
    }

    public function updateStatus(Request $request, $candidate)
    {
        return redirect()->back()->with('success', 'Status atualizado (stub).');
    }
}
