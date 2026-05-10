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
                    <span class="sidebar-badge">4</span>
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
                    <div>
                        <h1 class="text-3xl font-bold">Vagas</h1>
                        <p class="text-gray-500">Gerencie todas as suas vagas abertas</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('vagas.create-manual') }}" class="btn">
                            <i class="ti ti-plus"></i> Manual
                        </a>
                        <a href="{{ route('vagas.create-ia') }}" class="btn btn-primary">
                            <i class="ti ti-sparkles"></i> Nova Vaga com IA
                        </a>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="flex flex-col md:flex-row gap-4 justify-between mb-8">
                    <div class="flex border-b border-gray-200">
                        <button onclick="filterStatus('all')" class="tab-btn active px-6 py-3 font-semibold" id="tab-all">Todas (4)</button>
                        <button onclick="filterStatus('active')" class="tab-btn px-6 py-3 font-semibold text-gray-600" id="tab-active">Ativas (3)</button>
                        <button onclick="filterStatus('draft')" class="tab-btn px-6 py-3 font-semibold text-gray-600" id="tab-draft">Rascunhos (1)</button>
                    </div>

                    <div class="relative w-full md:w-96">
                        <i class="ti ti-search absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="text" id="search-input" 
                            onkeyup="filterVagas()"
                            class="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:border-blue-500"
                            placeholder="Buscar por cargo ou departamento...">
                    </div>
                </div>

                {{-- Lista de Vagas --}}
                <div id="vagas-list" class="space-y-4">

                    <a href="#" class="card p-6 vaga-item">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">
                                    <i class="ti ti-briefcase"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg">Gerente de Produto</h3>
                                    <p class="text-gray-500">Produto • Sênior • SP</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-green">Relatório Pronto</span>
                                <p class="text-sm text-gray-500 mt-1">7 candidatos • 5 dias</p>
                            </div>
                        </div>
                    </a>

                    <a href="#" class="card p-6 vaga-item">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">
                                    <i class="ti ti-code"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg">Desenvolvedor Backend Sênior</h3>
                                    <p class="text-gray-500">Tecnologia • Sênior • Remoto</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-amber">IA Processando</span>
                                <p class="text-sm text-gray-500 mt-1">4 candidatos • 2 dias</p>
                            </div>
                        </div>
                    </a>

                    <a href="#" class="card p-6 vaga-item">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-pink-100 text-pink-600 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">
                                    <i class="ti ti-palette"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg">Designer UX/UI</h3>
                                    <p class="text-gray-500">Design • Pleno • Híbrido</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-blue">Aguardando Testes</span>
                                <p class="text-sm text-gray-500 mt-1">5 candidatos • 8 dias</p>
                            </div>
                        </div>
                    </a>

                </div>

            </main>
        </div>
    </div>

    @push('scripts')
    <script>
    function filterStatus(status) {
        // Remove active de todos
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Ativa o botão clicado
        if (status === 'all') document.getElementById('tab-all').classList.add('active');
        if (status === 'active') document.getElementById('tab-active').classList.add('active');
        if (status === 'draft') document.getElementById('tab-draft').classList.add('active');
        
        // TODO: Implementar filtro real no futuro
        console.log('Filtro aplicado:', status);
    }

    function filterVagas() {
        const term = document.getElementById('search-input').value.toLowerCase().trim();
        const items = document.querySelectorAll('.vaga-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(term) ? 'block' : 'none';
        });
    }
    </script>
    @endpush

</x-app-layout>