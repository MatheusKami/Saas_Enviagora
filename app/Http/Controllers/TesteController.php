<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TestLink;
use App\Models\PersonalityResult;
use App\Models\Candidate;

class TesteController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /teste/{token}
    // Página de entrada do portal white-label
    // Mostra a logo da empresa, nome do candidato e o primeiro teste
    // ─────────────────────────────────────────────────────────
    public function show($token)
    {
        // Busca o link pelo token e carrega empresa e candidato juntos
        $link = TestLink::with(['company', 'candidate'])
            ->where('token', $token)
            ->first();

        // Link não existe
        if (!$link) {
            return view('teste.invalido', ['motivo' => 'Link não encontrado.']);
        }

        // Link expirado
        if ($link->expires_at && $link->expires_at->isPast()) {
            return view('teste.invalido', ['motivo' => 'Este link expirou. Solicite um novo ao RH.']);
        }

        // Link já foi usado
        if ($link->completed_at) {
            return redirect()->route('teste.resultado', $token);
        }

        // Verifica em qual etapa o candidato está (quantos testes já fez)
        $results     = PersonalityResult::where('subject_id', $link->candidate_id)
            ->where('subject_type', 'candidate')
            ->first();

        $etapaAtual = 1; // começa no DISC
        if ($results) {
            if ($results->disc_json)      $etapaAtual = 2; // já fez DISC, vai pro Eneagrama
            if ($results->enneagram_json) $etapaAtual = 3; // já fez Eneagrama, vai pro MBTI
            if ($results->mbti_json)      return redirect()->route('teste.resultado', $token);
        }

        return view('teste.show', [
            'link'       => $link,
            'company'    => $link->company,
            'candidate'  => $link->candidate,
            'etapaAtual' => $etapaAtual,
            'token'      => $token,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /teste/{token}/disc
    // Salva o resultado do teste DISC
    // ─────────────────────────────────────────────────────────
    public function salvarDisc(Request $request, $token)
    {
        $link = $this->getLinkValido($token);
        if (!$link) {
            return response()->json(['error' => 'Link inválido ou expirado'], 422);
        }

        $request->validate([
            'respostas' => 'required|array', // array de pares [mais, menos] para cada bloco
        ]);

        // Calcula os scores DISC a partir das respostas
        $scores = $this->calcularDisc($request->respostas);

        // Salva ou atualiza o resultado de personalidade do candidato
        PersonalityResult::updateOrCreate(
            [
                'subject_id'   => $link->candidate_id,
                'subject_type' => 'candidate',
            ],
            [
                'company_id' => $link->company_id,
                'disc_json'  => $scores,
            ]
        );

        return response()->json(['success' => true, 'proxima_etapa' => 2]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /teste/{token}/enneagram
    // Salva o resultado do teste Eneagrama
    // ─────────────────────────────────────────────────────────
    public function salvarEnneagram(Request $request, $token)
    {
        $link = $this->getLinkValido($token);
        if (!$link) {
            return response()->json(['error' => 'Link inválido ou expirado'], 422);
        }

        $request->validate([
            'respostas' => 'required|array',
        ]);

        $scores = $this->calcularEnneagram($request->respostas);

        PersonalityResult::where('subject_id', $link->candidate_id)
            ->where('subject_type', 'candidate')
            ->update(['enneagram_json' => $scores]);

        return response()->json(['success' => true, 'proxima_etapa' => 3]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /teste/{token}/mbti
    // Salva o resultado do teste 16 Personalidades (MBTI via IPIP)
    // ─────────────────────────────────────────────────────────
    public function salvarMbti(Request $request, $token)
    {
        $link = $this->getLinkValido($token);
        if (!$link) {
            return response()->json(['error' => 'Link inválido ou expirado'], 422);
        }

        $request->validate([
            'respostas' => 'required|array',
        ]);

        $scores = $this->calcularMbti($request->respostas);

        // Atualiza o MBTI e marca como completado
        PersonalityResult::where('subject_id', $link->candidate_id)
            ->where('subject_type', 'candidate')
            ->update([
                'mbti_json' => $scores,
                'completed' => true,
            ]);

        // Marca o link como usado e o candidato como completo
        $link->update(['completed_at' => now()]);

        if ($link->candidate) {
            $link->candidate->update(['test_completed_at' => now()]);
        }

        return response()->json([
            'success'  => true,
            'redirect' => route('teste.resultado', $token),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // GET /teste/{token}/resultado
    // Mostra o resultado resumido ao candidato depois de completar os 3 testes
    // ─────────────────────────────────────────────────────────
    public function resultado($token)
    {
        $link = TestLink::with(['company', 'candidate'])->where('token', $token)->first();

        if (!$link || !$link->completed_at) {
            return redirect()->route('teste.show', $token);
        }

        $results = PersonalityResult::where('subject_id', $link->candidate_id)
            ->where('subject_type', 'candidate')
            ->first();

        return view('teste.resultado', [
            'link'    => $link,
            'company' => $link->company,
            'results' => $results,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // ALGORITMOS DOS TESTES — implementação simplificada
    // Baseado nas bases open-source do briefing
    // ─────────────────────────────────────────────────────────

    // DISC — 28 blocos de 4 afirmações, candidato escolhe + e - para cada bloco
    // Cada afirmação tem um peso para D, I, S ou C
    // Respostas: array de objetos { mais: "D", menos: "S" }
    private function calcularDisc(array $respostas): array
    {
        // Contadores para cada dimensão
        $scores = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];

        foreach ($respostas as $resposta) {
            // "mais" vale +2 para a dimensão escolhida
            // "menos" vale -1 para a dimensão escolhida
            $mais  = strtoupper($resposta['mais']  ?? '');
            $menos = strtoupper($resposta['menos'] ?? '');

            if (isset($scores[$mais]))  $scores[$mais]  += 2;
            if (isset($scores[$menos])) $scores[$menos] -= 1;
        }

        // Garante que não fique negativo
        foreach ($scores as $k => $v) {
            $scores[$k] = max(0, $v);
        }

        // Calcula percentuais
        $total = array_sum($scores) ?: 1;
        $pcts  = [];
        foreach ($scores as $k => $v) {
            $pcts[$k] = round(($v / $total) * 100);
        }

        // Perfil dominante = dimensão com maior score
        arsort($pcts);
        $perfil = array_key_first($pcts);

        return [
            'perfil' => $perfil,
            'D'      => $pcts['D'],
            'I'      => $pcts['I'],
            'S'      => $pcts['S'],
            'C'      => $pcts['C'],
        ];
    }

    // Eneagrama — 9 tipos, cada resposta tem peso para um tipo específico
    // Respostas: array de { tipo: 1-9, valor: 1-5 }
    private function calcularEnneagram(array $respostas): array
    {
        // Soma os scores por tipo (1 a 9)
        $scores = array_fill(1, 9, 0);

        foreach ($respostas as $r) {
            $tipo  = (int)($r['tipo']  ?? 0);
            $valor = (int)($r['valor'] ?? 0);
            if ($tipo >= 1 && $tipo <= 9) {
                $scores[$tipo] += $valor;
            }
        }

        // Tipo principal = maior score
        arsort($scores);
        $tipoPrincipal = array_key_first($scores);

        // Asa = segundo tipo mais próximo (adjacente ao tipo principal)
        $asaCandidatos = [
            ($tipoPrincipal - 1 < 1 ? 9 : $tipoPrincipal - 1),
            ($tipoPrincipal + 1 > 9 ? 1 : $tipoPrincipal + 1),
        ];
        $asa = $scores[$asaCandidatos[0]] >= $scores[$asaCandidatos[1]]
            ? $asaCandidatos[0]
            : $asaCandidatos[1];

        return [
            'tipo'   => $tipoPrincipal,
            'asa'    => $asa,
            'scores' => $scores,
        ];
    }

    // MBTI via IPIP — 4 dicotomias, cada resposta indica preferência
    // Respostas: array de { dimensao: "EI"|"SN"|"TF"|"JP", valor: -2 a +2 }
    // Positivo = primeiro polo (E, S, T, J), negativo = segundo (I, N, F, P)
    private function calcularMbti(array $respostas): array
    {
        $somas = ['EI' => 0, 'SN' => 0, 'TF' => 0, 'JP' => 0];

        foreach ($respostas as $r) {
            $dim   = strtoupper($r['dimensao'] ?? '');
            $valor = (int)($r['valor'] ?? 0);
            if (isset($somas[$dim])) {
                $somas[$dim] += $valor;
            }
        }

        // Determina a letra de cada dicotomia
        $tipo  = '';
        $tipo .= $somas['EI'] >= 0 ? 'E' : 'I';
        $tipo .= $somas['SN'] >= 0 ? 'S' : 'N';
        $tipo .= $somas['TF'] >= 0 ? 'T' : 'F';
        $tipo .= $somas['JP'] >= 0 ? 'J' : 'P';

        // Percentuais de cada polo (0-100 onde 50 = neutro)
        $calcPct = fn($soma, $n) => $n > 0 ? min(100, max(0, 50 + round(($soma / ($n * 2)) * 50))) : 50;
        $nPorDim = count(array_filter($respostas, fn($r) => isset($r['dimensao']))) / 4 ?: 1;

        return [
            'tipo' => $tipo,
            'E'    => $calcPct($somas['EI'],  $nPorDim),
            'I'    => 100 - $calcPct($somas['EI'], $nPorDim),
            'S'    => $calcPct($somas['SN'],  $nPorDim),
            'N'    => 100 - $calcPct($somas['SN'], $nPorDim),
            'T'    => $calcPct($somas['TF'],  $nPorDim),
            'F'    => 100 - $calcPct($somas['TF'], $nPorDim),
            'J'    => $calcPct($somas['JP'],  $nPorDim),
            'P'    => 100 - $calcPct($somas['JP'], $nPorDim),
        ];
    }

    // Helper — busca o link pelo token e valida
    private function getLinkValido(string $token): ?TestLink
    {
        $link = TestLink::with('candidate')->where('token', $token)->first();
        if (!$link || !$link->isValido()) return null;
        return $link;
    }
}
