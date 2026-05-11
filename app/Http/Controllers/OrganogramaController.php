<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OrganogramaNode;

class OrganogramaController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /organograma
    // Página do organograma visual (drag-and-drop)
    // ─────────────────────────────────────────────────────────
    public function index()
    {
        return view('organograma.index');
    }

    // ─────────────────────────────────────────────────────────
    // GET /organograma/data
    // Retorna todos os nós da empresa como JSON
    // Usado pelo JS do drag-and-drop para renderizar o canvas
    // ─────────────────────────────────────────────────────────
    public function data()
    {
        $companyId = Auth::user()->company_id;

        $nodes = OrganogramaNode::where('company_id', $companyId)
            ->with('personalityResults') // inclui resultados de personalidade se tiver
            ->get()
            ->map(function ($node) {
                return [
                    'id'          => $node->id,
                    'nome'        => $node->nome,
                    'cargo'       => $node->cargo,
                    'departamento' => $node->departamento,
                    'email'       => $node->email,
                    'parent_id'   => $node->parent_id,
                    'pos_x'       => $node->pos_x,
                    'pos_y'       => $node->pos_y,
                    'tem_testes'  => $node->personalityResults?->completed ?? false,
                ];
            });

        return response()->json($nodes);
    }

    // ─────────────────────────────────────────────────────────
    // POST /organograma/nodes
    // Cria um novo nó (colaborador) no organograma
    // ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $request->validate([
            'nome'         => 'required|string|max:255',
            'cargo'        => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'parent_id'    => 'nullable|integer|exists:organograma_nodes,id',
            'pos_x'        => 'nullable|integer',
            'pos_y'        => 'nullable|integer',
        ]);

        $node = OrganogramaNode::create([
            'company_id'   => $companyId,
            'nome'         => $request->nome,
            'cargo'        => $request->cargo,
            'departamento' => $request->departamento,
            'email'        => $request->email,
            'parent_id'    => $request->parent_id,
            'pos_x'        => $request->pos_x ?? 0,
            'pos_y'        => $request->pos_y ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'node'    => $node,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /organograma/nodes/{id}
    // Atualiza um nó (nome, cargo, posição no canvas, etc.)
    // ─────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $companyId = Auth::user()->company_id;

        // Garante que o nó pertence a essa empresa — multi-tenancy
        $node = OrganogramaNode::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'nome'         => 'sometimes|string|max:255',
            'cargo'        => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'parent_id'    => 'nullable|integer|exists:organograma_nodes,id',
            'pos_x'        => 'nullable|integer',
            'pos_y'        => 'nullable|integer',
        ]);

        $node->update($request->only([
            'nome', 'cargo', 'departamento', 'email', 'parent_id', 'pos_x', 'pos_y'
        ]));

        return response()->json(['success' => true, 'node' => $node]);
    }

    // ─────────────────────────────────────────────────────────
    // DELETE /organograma/nodes/{id}
    // Remove um colaborador do organograma
    // ─────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $companyId = Auth::user()->company_id;
        $node      = OrganogramaNode::where('company_id', $companyId)->findOrFail($id);
        $node->delete();

        return response()->json(['success' => true]);
    }
}
