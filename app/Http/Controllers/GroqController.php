<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GroqController extends Controller
{
    public function chat()
    {
        return view('ia.chat');
    }

    public function enviarMensagem(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Chat stub - use VagaController logic or implement full Groq chat']);
    }

    public function gerarJobDescription(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Use VagaController::gerarJD instead']);
    }

    public function gerarPerguntas($job)
    {
        return response()->json(['success' => true, 'message' => 'Perguntas stub']);
    }
}
