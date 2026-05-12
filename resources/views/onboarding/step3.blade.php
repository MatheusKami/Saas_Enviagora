@extends('onboarding.layout')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Colaboradores atuais</h1>
    <p class="mt-1 text-gray-500">Me conta quantas pessoas você já tem em cada área. Isso ajuda o RHMatch a fazer matches mais precisos.</p>
</div>

<form action="{{ route('onboarding.save', 3) }}" method="POST" id="form-step3">
    @csrf

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Áreas e colaboradores</h2>
            <button type="button"
                    onclick="adicionarLinha()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Adicionar área
            </button>
        </div>

        {{-- Tabela de áreas --}}
        <div class="overflow-hidden border border-gray-200 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-3/4">Área / Departamento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colaboradores</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody id="areas-tbody" class="bg-white divide-y divide-gray-200">
                    {{-- Linhas dinâmicas geradas pelo JS --}}
                </tbody>
            </table>
        </div>

        {{-- Total --}}
        <div class="flex justify-end pt-2">
            <div class="bg-indigo-50 rounded-lg px-4 py-2 flex items-center gap-3">
                <span class="text-sm text-indigo-700 font-medium">Total de colaboradores:</span>
                <span id="total-colaboradores" class="text-xl font-bold text-indigo-900">0</span>
            </div>
        </div>
    </div>

    {{-- Sugestões rápidas de áreas comuns --}}
    <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-100">
        <p class="text-sm font-medium text-blue-900 mb-2">Sugestões rápidas:</p>
        <div class="flex flex-wrap gap-2">
            @foreach(['Tecnologia', 'Comercial', 'RH', 'Financeiro', 'Marketing', 'Operações', 'Jurídico', 'Atendimento', 'Logística', 'Produto'] as $area)
                <button type="button"
                        onclick="adicionarAreaRapida('{{ $area }}')"
                        class="px-3 py-1 text-xs font-medium text-blue-700 bg-white border border-blue-200 rounded-full hover:bg-blue-700 hover:text-white transition-colors">
                    + {{ $area }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Navegação --}}
    <div class="flex justify-between mt-6">
        <a href="{{ route('onboarding.step', 2) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>

        <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
            Salvar e continuar
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
let contadorLinhas = 0;

// Carrego dados existentes se já preencheu antes
@if($company->colaboradores_por_area)
    const dadosExistentes = @json($company->colaboradores_por_area);
    dadosExistentes.forEach(area => adicionarLinha(area.nome, area.quantidade));
@else
    // Começo com 3 linhas em branco pra facilitar
    adicionarLinha();
    adicionarLinha();
    adicionarLinha();
@endif

// =====================================================
// Adiciona uma nova linha na tabela
// =====================================================
function adicionarLinha(nome = '', quantidade = 0) {
    contadorLinhas++;
    const idx = contadorLinhas;

    const tbody = document.getElementById('areas-tbody');
    const tr = document.createElement('tr');
    tr.id = `linha-${idx}`;
    tr.innerHTML = `
        <td class="px-4 py-3">
            <input type="text"
                   name="areas[${idx}][nome]"
                   value="${escapeHtml(nome)}"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                   placeholder="Ex: Tecnologia"
                   oninput="atualizarTotal()">
        </td>
        <td class="px-4 py-3">
            <input type="number"
                   name="areas[${idx}][quantidade]"
                   value="${quantidade}"
                   min="0"
                   max="99999"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-center"
                   oninput="atualizarTotal()">
        </td>
        <td class="px-4 py-3">
            <button type="button"
                    onclick="removerLinha(${idx})"
                    class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    atualizarTotal();
}

// Adiciona área pela sugestão rápida
function adicionarAreaRapida(nome) {
    adicionarLinha(nome, 0);
    // Foco no campo de quantidade da linha recém adicionada
    const inputs = document.querySelectorAll(`#linha-${contadorLinhas} input[type="number"]`);
    if (inputs.length) inputs[0].focus();
}

// Remove uma linha
function removerLinha(idx) {
    const linha = document.getElementById(`linha-${idx}`);
    if (linha) linha.remove();
    atualizarTotal();
}

// Calcula e mostra o total de colaboradores
function atualizarTotal() {
    const inputs = document.querySelectorAll('input[name$="[quantidade]"]');
    let total = 0;
    inputs.forEach(input => {
        total += parseInt(input.value || 0);
    });
    document.getElementById('total-colaboradores').textContent = total.toLocaleString('pt-BR');
}

// Escapa HTML pra evitar XSS nos valores preenchidos
function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
@endpush
