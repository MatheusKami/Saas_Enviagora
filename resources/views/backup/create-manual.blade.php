<x-app-layout>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
@endpush

<div class="dash-wrap">

    {{-- Topbar --}}
    <header class="dash-topbar">
        <a href="{{ route('dashboard') }}" class="topbar-brand">
            <div class="topbar-brand-icon"><i class="ti ti-users"></i></div>
            RHMatch
        </a>
        <div class="topbar-actions">
            <a href="#" class="topbar-icon-btn" title="Notificações">
                <i class="ti ti-bell" style="font-size:17px"></i>
                <span class="notif-dot"></span>
            </a>
            <div class="topbar-avatar" title="{{ Auth::user()->name }}">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
        </div>
    </header>

    <div class="dash-layout">

        {{-- Sidebar --}}
        <aside class="dash-sidebar">
            <span class="sidebar-section">Principal</span>
            <a href="{{ route('dashboard') }}" class="sidebar-link">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>
            <a href="{{ route('vagas.index') }}" class="sidebar-link active">
                <i class="ti ti-briefcase"></i> Vagas
            </a>
            <a href="#" class="sidebar-link">
                <i class="ti ti-users"></i> Candidatos
            </a>

            <span class="sidebar-section">Empresa</span>
            <a href="#" class="sidebar-link">
                <i class="ti ti-sitemap"></i> Organograma
            </a>
            <a href="#" class="sidebar-link">
                <i class="ti ti-brain"></i> Testes
            </a>
            <a href="#" class="sidebar-link">
                <i class="ti ti-settings"></i> Configurações
            </a>

            <div class="sidebar-spacer"></div>

            <a href="{{ route('logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
               class="sidebar-link" style="color:var(--red)">
                <i class="ti ti-logout"></i> Sair
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                @csrf
            </form>
        </aside>

        {{-- Conteúdo --}}
        <main class="dash-content">

            <div class="page-header">
                <div class="flex items-center gap-3">
                    <a href="{{ route('vagas.index') }}" class="text-gray-500 hover:text-gray-700">
                        <i class="ti ti-arrow-left" style="font-size:24px"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold">Criar Vaga Manualmente</h1>
                        <p class="text-gray-500">Preencha os dados da vaga e adicione os candidatos</p>
                    </div>
                </div>
            </div>

            <div class="max-w-5xl mx-auto">
                <form id="form-vaga-manual" class="space-y-10">

                    {{-- Dados da Vaga --}}
                    <div class="card p-8">
                        <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
                            <i class="ti ti-briefcase"></i>
                            Dados da Vaga
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold mb-2">Cargo da vaga <span class="text-red-500">*</span></label>
                                <input type="text" id="cargo" required
                                       class="w-full px-5 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:border-blue-600"
                                       placeholder="Ex: Gerente de Produto Sênior">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Departamento</label>
                                <input type="text" id="departamento" class="w-full px-5 py-3 border border-gray-300 rounded-2xl">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Líder direto</label>
                                <select id="lider" class="w-full px-5 py-3 border border-gray-300 rounded-2xl">
                                    <option value="">Selecione...</option>
                                    <option value="1">Maria Silva - Head de Produto</option>
                                    <option value="2">João Mendes - CTO</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Senioridade</label>
                                <select id="senioridade" class="w-full px-5 py-3 border border-gray-300 rounded-2xl">
                                    <option value="pleno" selected>Pleno</option>
                                    <option value="senior">Sênior</option>
                                    <option value="lider">Líder</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold mb-2">Principais responsabilidades</label>
                                <textarea id="responsabilidades" rows="5" required
                                    class="w-full px-5 py-3 border border-gray-300 rounded-3xl focus:outline-none focus:border-blue-600"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Candidatos --}}
                    <div class="card p-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold flex items-center gap-3">
                                <i class="ti ti-users"></i> Candidatos
                            </h2>
                            <button type="button" onclick="adicionarCandidato()" 
                                    class="btn btn-primary flex items-center gap-2">
                                <i class="ti ti-plus"></i> Adicionar Candidato
                            </button>
                        </div>

                        <div id="lista-candidatos" class="space-y-6"></div>
                    </div>

                    <div class="flex gap-4 justify-end">
                        <a href="{{ route('vagas.index') }}" class="px-8 py-3 font-semibold text-gray-600 hover:bg-gray-100 rounded-2xl">
                            Cancelar
                        </a>
                        <button type="button" onclick="salvarVagaManual()" 
                                class="btn btn-primary px-10 py-3">
                            Salvar Vaga
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>
</div>

@push('scripts')
<script>
let candidatoCount = 0;

const candidatoTemplate = (index) => `
    <div class="candidato-item border border-gray-200 rounded-3xl p-6" data-index="${index}">
        <div class="flex justify-between mb-4">
            <h3 class="font-semibold">Candidato #${index + 1}</h3>
            <button type="button" onclick="removerCandidato(this)" class="text-red-500">
                <i class="ti ti-trash"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium mb-1">Nome completo</label>
                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-2xl">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">LinkedIn</label>
                <input type="url" class="w-full px-4 py-3 border border-gray-300 rounded-2xl">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Currículo (PDF)</label>
                <input type="file" accept=".pdf" class="w-full">
            </div>
        </div>
    </div>
`;

function adicionarCandidato() {
    candidatoCount++;
    document.getElementById('lista-candidatos').insertAdjacentHTML('beforeend', candidatoTemplate(candidatoCount));
}

function removerCandidato(btn) {
    if (confirm('Remover candidato?')) btn.closest('.candidato-item').remove();
}

function salvarVagaManual() {
    const cargo = document.getElementById('cargo').value;
    if (!cargo) return alert("Preencha o cargo da vaga.");
    alert(`Vaga "${cargo}" salva com sucesso!`);
}

window.onload = () => adicionarCandidato();
</script>
@endpush

</x-app-layout>