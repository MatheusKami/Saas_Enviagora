@extends('onboarding.layout')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Organograma da empresa</h1>
    <p class="mt-1 text-gray-500">Adicione os departamentos e a hierarquia. O RHMatch usa isso pra entender a estrutura da sua empresa.</p>
</div>

<form action="{{ route('onboarding.save', 2) }}" method="POST">
    @csrf

    {{-- Input hidden que vai receber o JSON do organograma --}}
    <input type="hidden" name="organograma" id="organograma-data">

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">

        {{-- Adicionar departamento --}}
        <div>
            <h2 class="text-base font-semibold text-gray-900 mb-4">Departamentos</h2>

            <div class="flex gap-2 mb-4">
                <input type="text"
                       id="novo-departamento"
                       class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="Ex: Tecnologia, RH, Comercial..."
                       onkeydown="if(event.key === 'Enter') { event.preventDefault(); adicionarDepartamento(); }">

                <select id="novo-dept-pai"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Sem departamento pai (raiz)</option>
                </select>

                <button type="button"
                        onclick="adicionarDepartamento()"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Adicionar
                </button>
            </div>

            {{-- Lista de departamentos adicionados --}}
            <div id="departamentos-lista" class="space-y-2 min-h-16">
                {{-- Os departamentos aparecem aqui dinamicamente --}}
                <div id="lista-vazia" class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-sm">Nenhum departamento adicionado ainda</p>
                    <p class="text-xs mt-1">Comece adicionando o departamento raiz (Ex: Diretoria)</p>
                </div>
            </div>
        </div>

        {{-- Visualização da árvore --}}
        <div id="arvore-wrapper" class="hidden">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Visualização da hierarquia</h2>
            <div id="arvore-organograma"
                 class="bg-gray-50 rounded-lg p-4 border border-gray-200 overflow-x-auto">
                {{-- Renderizado via JS --}}
            </div>
        </div>

    </div>

    {{-- Navegação --}}
    <div class="flex justify-between mt-6">
        <a href="{{ route('onboarding.step', 1) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>

        <button type="submit"
                onclick="prepararSubmit()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
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
// Estrutura dos departamentos em memória
// Cada item: { id, nome, pai_id }
let departamentos = [];

// Carrego dados existentes se o usuário já preencheu antes
@if($company->organograma)
    departamentos = @json($company->organograma);
    renderizarLista();
    renderizarArvore();
@endif

// =====================================================
// Adiciona um novo departamento à lista
// =====================================================
function adicionarDepartamento() {
    const input = document.getElementById('novo-departamento');
    const paiSelect = document.getElementById('novo-dept-pai');
    const nome = input.value.trim();

    if (!nome) {
        input.focus();
        return;
    }

    // Crio um ID único simples baseado em timestamp
    const novoDept = {
        id:     Date.now(),
        nome:   nome,
        pai_id: paiSelect.value ? parseInt(paiSelect.value) : null,
    };

    departamentos.push(novoDept);

    // Limpo o campo e renderizo
    input.value = '';
    input.focus();
    renderizarLista();
    renderizarArvore();
    atualizarSelectPai();
}

// =====================================================
// Remove um departamento (e seus filhos)
// =====================================================
function removerDepartamento(id) {
    // Remove o item e todos que têm ele como pai (recursivo)
    const idsParaRemover = new Set();

    function coletarFilhos(paiId) {
        departamentos.forEach(d => {
            if (d.pai_id === paiId) {
                idsParaRemover.add(d.id);
                coletarFilhos(d.id);
            }
        });
    }

    idsParaRemover.add(id);
    coletarFilhos(id);

    departamentos = departamentos.filter(d => !idsParaRemover.has(d.id));
    renderizarLista();
    renderizarArvore();
    atualizarSelectPai();
}

// =====================================================
// Renderiza a lista de departamentos adicionados
// =====================================================
function renderizarLista() {
    const lista = document.getElementById('departamentos-lista');
    const vazia = document.getElementById('lista-vazia');

    if (departamentos.length === 0) {
        lista.innerHTML = '';
        lista.appendChild(vazia);
        vazia.classList.remove('hidden');
        return;
    }

    vazia.classList.add('hidden');

    lista.innerHTML = departamentos.map(dept => {
        const pai = departamentos.find(d => d.id === dept.pai_id);
        return `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                    <span class="text-sm font-medium text-gray-900">${dept.nome}</span>
                    ${pai ? `<span class="text-xs text-gray-500">← ${pai.nome}</span>` : '<span class="text-xs text-indigo-600 font-medium">Raiz</span>'}
                </div>
                <button type="button"
                        onclick="removerDepartamento(${dept.id})"
                        class="text-gray-400 hover:text-red-500 transition-colors p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;
    }).join('');
}

// =====================================================
// Renderiza a árvore visual do organograma
// =====================================================
function renderizarArvore() {
    const wrapper = document.getElementById('arvore-wrapper');
    const container = document.getElementById('arvore-organograma');

    if (departamentos.length === 0) {
        wrapper.classList.add('hidden');
        return;
    }

    wrapper.classList.remove('hidden');

    // Pego só os nós raiz (sem pai)
    const raizes = departamentos.filter(d => !d.pai_id);

    function renderNodo(dept, nivel = 0) {
        const filhos = departamentos.filter(d => d.pai_id === dept.id);
        const indent = nivel * 24;
        return `
            <div style="padding-left: ${indent}px" class="py-1">
                <div class="inline-flex items-center gap-1.5">
                    ${nivel > 0 ? '<div class="w-4 h-px bg-gray-300 -ml-4"></div>' : ''}
                    <span class="text-sm ${nivel === 0 ? 'font-semibold text-gray-900' : 'text-gray-700'}">${dept.nome}</span>
                </div>
                ${filhos.map(f => renderNodo(f, nivel + 1)).join('')}
            </div>
        `;
    }

    container.innerHTML = raizes.map(r => renderNodo(r)).join('');
}

// =====================================================
// Atualiza o select de departamento pai com os existentes
// =====================================================
function atualizarSelectPai() {
    const select = document.getElementById('novo-dept-pai');
    const valorAtual = select.value;

    select.innerHTML = '<option value="">Sem departamento pai (raiz)</option>';
    departamentos.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.nome;
        if (opt.value == valorAtual) opt.selected = true;
        select.appendChild(opt);
    });
}

// =====================================================
// Antes de submeter, serializo o organograma em JSON
// =====================================================
function prepararSubmit() {
    if (departamentos.length === 0) {
        alert('Adicione pelo menos um departamento no organograma.');
        return false;
    }
    document.getElementById('organograma-data').value = JSON.stringify(departamentos);
    return true;
}

// Enter no campo de departamento adiciona
document.getElementById('novo-departamento').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        adicionarDepartamento();
    }
});
</script>
@endpush
