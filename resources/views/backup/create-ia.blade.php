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

                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="sidebar-link" style="color:var(--red)">
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
                            <h1 class="text-2xl font-bold">Nova Vaga com IA</h1>
                            <p class="text-gray-500">Descreva o cargo e deixe a IA gerar tudo</p>
                        </div>
                    </div>
                </div>

                <div class="max-w-6xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        {{-- Briefing --}}
                        <div class="lg:col-span-7">
                            <div class="card p-8">
                                <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
                                    <i class="ti ti-sparkles text-amber-500"></i>
                                    Briefing da Vaga
                                </h2>

                                <form id="form-ia" class="space-y-8">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Cargo da vaga <span class="text-red-500">*</span></label>
                                        <input type="text" id="cargo" required class="w-full px-5 py-3.5 border border-gray-300 rounded-2xl focus:outline-none focus:border-blue-600 text-lg" placeholder="Ex: Gerente de Produto Sênior">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold mb-2">Líder direto</label>
                                            <select id="lider" class="w-full px-5 py-3.5 border border-gray-300 rounded-2xl">
                                                <option value="">Selecione do organograma...</option>
                                                <option value="1">Maria Silva - Head de Produto</option>
                                                <option value="2">João Mendes - CTO</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold mb-2">Senioridade</label>
                                            <select id="senioridade" class="w-full px-5 py-3.5 border border-gray-300 rounded-2xl">
                                                <option value="pleno" selected>Pleno</option>
                                                <option value="senior">Sênior</option>
                                                <option value="lider">Líder</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Principais responsabilidades e desafios</label>
                                        <textarea id="responsabilidades" rows="6" required class="w-full px-5 py-3.5 border border-gray-300 rounded-3xl focus:outline-none focus:border-blue-600" placeholder="Descreva o dia a dia da posição..."></textarea>
                                    </div>

                                    <div class="pt-4">
                                        <button type="button" onclick="gerarVagaComIA()" class="w-full btn btn-primary text-lg py-4 flex items-center justify-center gap-3">
                                            <i class="ti ti-sparkles"></i> Gerar Vaga com IA
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Resultado IA --}}
                        <div class="lg:col-span-5">
                            <div class="card p-8 h-full min-h-[620px] flex flex-col">
                                <h2 class="text-xl font-bold mb-6">Resultado gerado pela IA</h2>

                                <div id="ia-placeholder" class="flex-1 flex items-center justify-center text-center">
                                    <div>
                                        <i class="ti ti-sparkles text-6xl text-blue-200 mb-6"></i>
                                        <p class="text-gray-500">Preencha o briefing e clique no botão acima</p>
                                    </div>
                                </div>

                                <div id="ia-result" class="hidden flex-1 flex flex-col">
                                    <div class="flex justify-between mb-6">
                                        <h3 class="font-bold text-2xl" id="ia-cargo-title"></h3>
                                        <span class="badge badge-green">Gerado por IA</span>
                                    </div>
                                    <div class="flex-1 space-y-6 overflow-auto">
                                        <div id="ia-jd" class="prose prose-sm"></div>
                                        <div class="grid grid-cols-2 gap-6">
                                            <div>
                                                <p class="font-semibold">Faixa Salarial</p>
                                                <p id="ia-salario" class="text-2xl font-bold text-emerald-600"></p>
                                            </div>
                                            <div>
                                                <p class="font-semibold">Perfil Ideal</p>
                                                <p id="ia-perfil" class="font-medium"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-6 border-t flex gap-3">
                                        <button onclick="salvarVaga()" class="flex-1 py-4 rounded-2xl border">Salvar Rascunho</button>
                                        <button onclick="publicarVaga()" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl">Publicar Vaga</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>


</x-app-layout>