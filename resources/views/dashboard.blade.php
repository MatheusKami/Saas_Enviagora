<x-app-layout>
    {{-- Redireciona para onboarding se não tiver empresa cadastrada --}}
    @if(!Auth::user()->company_id)
        <script>window.location.href = '/onboarding';</script>
    @endif

    {{-- Cabeçalho --}}
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p id="greeting-line">Carregando...</p>
        </div>
        <a href="{{ route('vagas.create-ia') }}" class="btn btn-primary">
            <i class="ti ti-plus" style="font-size:15px"></i> Nova vaga
        </a>
    </div>

    {{-- ── Card da empresa ─────────────────────────────────────── --}}
    @if($company)
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header" style="align-items:flex-start;gap:1rem">

            {{-- Logo ou iniciais --}}
            <div style="flex-shrink:0">
                @if($company->logo_url)
                    <img src="{{ $company->logo_url }}" alt="Logo"
                         style="height:56px;width:56px;object-fit:contain;border-radius:10px;border:1px solid var(--gray-200);padding:4px;background:#fff">
                @else
                    <div style="height:56px;width:56px;border-radius:10px;background:var(--blue-50,#ddeeff);
                                color:var(--blue-700,#185FA5);display:flex;align-items:center;
                                justify-content:center;font-weight:700;font-size:1.2rem">
                        {{ strtoupper(substr($company->razao_social, 0, 2)) }}
                    </div>
                @endif
            </div>

            {{-- Dados principais --}}
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                    <h2 style="font-size:1.1rem;font-weight:700;margin:0">{{ $company->razao_social }}</h2>

                    {{-- Badge de perfil/ritmo --}}
                    @if($company->perfil_ritmo)
                        @php
                            $badges = [
                                'dinamico'    => ['label' => 'Dinâmico / Ágil',         'class' => 'badge-blue'],
                                'analitico'   => ['label' => 'Analítico / Estruturado',  'class' => 'badge-gray'],
                                'equilibrado' => ['label' => 'Equilibrado',              'class' => 'badge-green'],
                                'criativo'    => ['label' => 'Criativo / Inovador',      'class' => 'badge-amber'],
                            ];
                            $badge = $badges[$company->perfil_ritmo] ?? ['label' => $company->perfil_ritmo, 'class' => 'badge-gray'];
                        @endphp
                        <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                    @endif
                </div>

                {{-- Metadados: CNPJ, site, endereço --}}
                <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.25rem;margin-top:.4rem">
                    @if($company->cnpj)
                        <span style="font-size:.8rem;color:var(--gray-500)">
                            <i class="ti ti-id-badge-2"></i> {{ $company->cnpj }}
                        </span>
                    @endif
                    @if($company->url_empresa)
                        <a href="{{ $company->url_empresa }}" target="_blank"
                           style="font-size:.8rem;color:var(--blue-600,#185FA5);text-decoration:none">
                            <i class="ti ti-world"></i> {{ parse_url($company->url_empresa, PHP_URL_HOST) }}
                        </a>
                    @endif
                    @if($company->endereco_completo)
                        <span style="font-size:.8rem;color:var(--gray-500)">
                            <i class="ti ti-map-pin"></i> {{ $company->endereco_completo }}
                        </span>
                    @endif
                </div>

                {{-- Contexto --}}
                @if($company->contexto_empresa)
                    <p style="font-size:.82rem;color:var(--gray-600);margin:.6rem 0 0;line-height:1.5">
                        {{ Str::limit($company->contexto_empresa, 160) }}
                    </p>
                @endif

                {{-- Valores --}}
                @if(!empty($company->valores))
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.6rem">
                        @foreach($company->valores as $valor)
                            <span style="font-size:.75rem;padding:.2rem .55rem;border-radius:99px;
                                         background:var(--gray-100,#f3f4f6);color:var(--gray-600)">
                                {{ $valor }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Link editar --}}
            <a href="{{ route('editar_empresa') }}"
               style="flex-shrink:0;font-size:.8rem;color:var(--gray-400);text-decoration:none;
                      display:flex;align-items:center;gap:.25rem"
               title="Editar empresa">
                <i class="ti ti-pencil"></i> Editar
            </a>
        </div>
    </div>
    @endif

    {{-- CTA Banner --}}
    <a href="{{ route('vagas.create-ia') }}" class="cta-banner">
        <div class="cta-left">
            <div class="cta-icon"><i class="ti ti-sparkles"></i></div>
            <div>
                <div class="cta-title">Criar nova vaga com IA</div>
                <div class="cta-sub">Descreva o cargo e a IA gera a JD, salário estimado e perfil psicométrico ideal</div>
            </div>
        </div>
        <span class="btn-white">
            Começar <i class="ti ti-arrow-right" style="font-size:14px"></i>
        </span>
    </a>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label"><i class="ti ti-briefcase"></i> Vagas ativas</div>
            <div class="stat-value">4</div>
            <div class="stat-sub">2 aguardando candidatos</div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><i class="ti ti-users"></i> Candidatos em processo</div>
            <div class="stat-value">18</div>
            <div class="stat-sub">6 com testes concluídos</div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><i class="ti ti-clock"></i> Links aguardando</div>
            <div class="stat-value">5</div>
            <div class="stat-sub"><span class="warn">1 expira em 12h</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><i class="ti ti-chart-bar"></i> Relatórios gerados</div>
            <div class="stat-value">11</div>
            <div class="stat-sub">este mês</div>
        </div>
    </div>

    {{-- Vagas + Candidatos --}}
    <div class="two-col">

        <div class="card">
            <div class="card-header">
                <span class="card-title">Vagas ativas</span>
                <a href="{{ route('vagas.index') }}" class="card-link">Ver todas <i class="ti ti-arrow-right" style="font-size:12px"></i></a>
            </div>
            <a href="#" class="vaga-item">
                <div class="vaga-ico"><i class="ti ti-briefcase"></i></div>
                <div class="vaga-info">
                    <div class="vaga-title">Gerente de produto</div>
                    <div class="vaga-meta"><span><i class="ti ti-users"></i> 7 candidatos</span><span><i class="ti ti-calendar"></i> 5 dias</span></div>
                </div>
                <span class="badge badge-green"><i class="ti ti-check"></i> Relatório pronto</span>
            </a>
            <a href="#" class="vaga-item">
                <div class="vaga-ico"><i class="ti ti-code"></i></div>
                <div class="vaga-info">
                    <div class="vaga-title">Dev backend sênior</div>
                    <div class="vaga-meta"><span><i class="ti ti-users"></i> 4 candidatos</span><span><i class="ti ti-calendar"></i> 2 dias</span></div>
                </div>
                <span class="badge badge-amber"><i class="ti ti-loader"></i> IA processando</span>
            </a>
            <a href="#" class="vaga-item">
                <div class="vaga-ico"><i class="ti ti-palette"></i></div>
                <div class="vaga-info">
                    <div class="vaga-title">Designer UX</div>
                    <div class="vaga-meta"><span><i class="ti ti-users"></i> 5 candidatos</span><span><i class="ti ti-calendar"></i> 8 dias</span></div>
                </div>
                <span class="badge badge-blue"><i class="ti ti-clock"></i> Aguardando testes</span>
            </a>
            <a href="#" class="vaga-item">
                <div class="vaga-ico"><i class="ti ti-chart-bar"></i></div>
                <div class="vaga-info">
                    <div class="vaga-title">Analista financeiro</div>
                    <div class="vaga-meta"><span><i class="ti ti-users"></i> 2 candidatos</span><span><i class="ti ti-calendar"></i> Hoje</span></div>
                </div>
                <span class="badge badge-gray">Rascunho</span>
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Candidatos em destaque</span>
                <a href="#" class="card-link">Ver todos <i class="ti ti-arrow-right" style="font-size:12px"></i></a>
            </div>
            <a href="#" class="cand-item">
                <div class="cand-avatar" style="background:#ddeeff;color:#185FA5">AS</div>
                <div class="cand-info"><div class="cand-name">Ana Souza</div><div class="cand-vaga">Gerente de produto</div></div>
                <div class="cand-score"><span class="score-num">87%</span><div class="score-bar-wrap"><div class="score-bar" style="width:87%"></div></div></div>
            </a>
            <a href="#" class="cand-item">
                <div class="cand-avatar" style="background:#eaf6f0;color:#1D9E75">CL</div>
                <div class="cand-info"><div class="cand-name">Carlos Lima</div><div class="cand-vaga">Gerente de produto</div></div>
                <div class="cand-score"><span class="score-num" style="color:#1D9E75">71%</span><div class="score-bar-wrap"><div class="score-bar" style="width:71%;background:#1D9E75"></div></div></div>
            </a>
            <a href="#" class="cand-item">
                <div class="cand-avatar" style="background:#fef3e2;color:#BA7517">BN</div>
                <div class="cand-info"><div class="cand-name">Beatriz Neves</div><div class="cand-vaga">Designer UX</div></div>
                <div class="cand-score"><span class="score-num" style="color:#BA7517">79%</span><div class="score-bar-wrap"><div class="score-bar" style="width:79%;background:#BA7517"></div></div></div>
            </a>
            <a href="#" class="cand-item">
                <div class="cand-avatar" style="background:#f3f0fe;color:#7F77DD">RF</div>
                <div class="cand-info"><div class="cand-name">Rafael Fernandes</div><div class="cand-vaga">Dev backend sênior</div></div>
                <div class="cand-score"><span class="score-num" style="color:var(--gray-400)">—</span><div class="score-bar-wrap"><div class="score-bar" style="width:0%"></div></div></div>
            </a>
        </div>

    </div>

    {{-- Atividade recente --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Atividade recente</span>
        </div>
        <div class="atv-item">
            <div class="atv-track"><div class="atv-dot" style="background:#1D9E75"></div><div class="atv-line"></div></div>
            <div class="atv-body">
                <div class="atv-text"><strong>Ana Souza</strong> concluiu os 3 testes psicométricos · Gerente de produto</div>
                <div class="atv-time">Há 23 minutos</div>
            </div>
        </div>
        <div class="atv-item">
            <div class="atv-track"><div class="atv-dot" style="background:#185FA5"></div><div class="atv-line"></div></div>
            <div class="atv-body">
                <div class="atv-text">Relatório de match gerado para <strong>Gerente de produto</strong> — 7 candidatos ranqueados</div>
                <div class="atv-time">Há 1 hora</div>
            </div>
        </div>
        <div class="atv-item">
            <div class="atv-track"><div class="atv-dot" style="background:#BA7517"></div><div class="atv-line"></div></div>
            <div class="atv-body">
                <div class="atv-text">Link de testes enviado para <strong>Rafael Fernandes</strong> · expira em 12h</div>
                <div class="atv-time">Há 3 horas</div>
            </div>
        </div>
        <div class="atv-item">
            <div class="atv-track"><div class="atv-dot" style="background:#185FA5"></div></div>
            <div class="atv-body">
                <div class="atv-text">Vaga <strong>Dev backend sênior</strong> criada com JD gerada por IA</div>
                <div class="atv-time">Hoje, 09:14</div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const h = new Date().getHours();
        const g = h < 12 ? 'Bom dia' : h < 18 ? 'Boa tarde' : 'Boa noite';
        const d = new Date().toLocaleDateString('pt-BR', {weekday:'long',day:'numeric',month:'long',year:'numeric'});
        document.getElementById('greeting-line').textContent = `${g}, {{ Auth::user()->name }} — ${d}`;
    </script>
    @endpush

</x-app-layout>
