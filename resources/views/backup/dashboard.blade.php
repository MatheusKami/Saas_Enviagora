<x-app-layout>

{{-- ═══════════════════════════════════════════════════════
     DASHBOARD — RHMatch
     Requer: Tailwind CSS (já incluso no Breeze/Jetstream)
     Ícones: Tabler Icons CDN (carregado via @push styles)
     Salvar em: resources/views/dashboard/index.blade.php
════════════════════════════════════════════════════════ --}}

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
    :root {
        --blue-900: #0a2f52;
        --blue-800: #0c3d6b;
        --blue-600: #185FA5;
        --blue-400: #378ADD;
        --blue-100: #ddeeff;
        --blue-50:  #f0f7ff;
        --green:    #1D9E75;
        --amber:    #BA7517;
        --red:      #C0392B;
        --gray-50:  #f8f9fb;
        --gray-100: #f0f2f5;
        --gray-200: #e2e6ec;
        --gray-400: #9ba3b0;
        --gray-600: #5a6473;
        --gray-900: #111827;
        --radius:   10px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        --shadow-md: 0 4px 12px rgba(0,0,0,.08);
        --shadow-blue: 0 4px 16px rgba(24,95,165,.25);
    }

    .dash-wrap {
        min-height: 100vh;
        background: var(--gray-50);
        font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
        color: var(--gray-900);
    }

    /* ── Topbar ── */
    .dash-topbar {
        background: #fff;
        border-bottom: 1px solid var(--gray-200);
        padding: 0 2rem;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 40;
    }
    .topbar-brand {
        display: flex; align-items: center; gap: .6rem;
        font-size: 1rem; font-weight: 700;
        color: var(--gray-900); text-decoration: none;
    }
    .topbar-brand-icon {
        width: 30px; height: 30px;
        background: var(--blue-600); border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 15px;
    }
    .topbar-actions { display: flex; align-items: center; gap: .6rem; }
    .topbar-icon-btn {
        width: 36px; height: 36px;
        border: 1px solid var(--gray-200); border-radius: 8px;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        color: var(--gray-600); cursor: pointer;
        transition: background .15s, border-color .15s;
        text-decoration: none; position: relative;
    }
    .topbar-icon-btn:hover { background: var(--gray-100); border-color: var(--gray-400); }
    .notif-dot {
        position: absolute; top: 6px; right: 6px;
        width: 7px; height: 7px;
        background: var(--red); border-radius: 50%;
        border: 1.5px solid #fff;
    }
    .topbar-avatar {
        width: 34px; height: 34px;
        background: var(--blue-50); border: 1px solid var(--blue-100); border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; color: var(--blue-600); cursor: pointer;
    }

    /* ── Layout ── */
    .dash-layout { display: flex; min-height: calc(100vh - 60px); }

    .dash-sidebar {
        width: 220px; flex-shrink: 0;
        background: #fff; border-right: 1px solid var(--gray-200);
        padding: 1.25rem .75rem;
        display: flex; flex-direction: column; gap: .2rem;
        position: sticky; top: 60px;
        height: calc(100vh - 60px); overflow-y: auto;
    }
    .sidebar-section {
        font-size: .68rem; font-weight: 600;
        letter-spacing: .06em; text-transform: uppercase;
        color: var(--gray-400); padding: .75rem .6rem .3rem;
    }
    .sidebar-link {
        display: flex; align-items: center; gap: .55rem;
        padding: .5rem .7rem; border-radius: 8px;
        font-size: .82rem; color: var(--gray-600);
        text-decoration: none; transition: background .12s, color .12s;
    }
    .sidebar-link:hover { background: var(--gray-100); color: var(--gray-900); }
    .sidebar-link.active { background: var(--blue-50); color: var(--blue-600); font-weight: 600; }
    .sidebar-link i { font-size: 16px; flex-shrink: 0; }
    .sidebar-badge {
        margin-left: auto;
        background: var(--blue-600); color: #fff;
        font-size: .6rem; font-weight: 600;
        padding: 1px 6px; border-radius: 20px;
    }
    .sidebar-spacer { flex: 1; }

    /* ── Conteúdo ── */
    .dash-content {
        flex: 1; min-width: 0;
        padding: 1.75rem 2rem 3rem;
        display: flex; flex-direction: column; gap: 1.5rem;
    }

    /* ── Page header ── */
    .page-header {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .page-header h1 { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .page-header p { font-size: .8rem; color: var(--gray-400); margin-top: .15rem; }

    .btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem 1.1rem; border-radius: 8px;
        font-size: .83rem; font-weight: 600;
        cursor: pointer; border: none; text-decoration: none;
        transition: opacity .15s, transform .1s;
    }
    .btn:active { transform: scale(.97); }
    .btn-primary { background: var(--blue-600); color: #fff; box-shadow: var(--shadow-blue); }
    .btn-primary:hover { opacity: .88; }

    /* ── CTA Banner ── */
    .cta-banner {
        background: linear-gradient(120deg, var(--blue-800) 0%, var(--blue-600) 100%);
        border-radius: var(--radius); padding: 1.3rem 1.75rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        box-shadow: var(--shadow-blue); text-decoration: none;
        position: relative; overflow: hidden; transition: opacity .15s;
    }
    .cta-banner:hover { opacity: .93; }
    .cta-banner::before {
        content: ''; position: absolute; right: -40px; top: -40px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,.06); border-radius: 50%;
    }
    .cta-left { display: flex; align-items: center; gap: 1rem; position: relative; z-index: 1; }
    .cta-icon {
        width: 44px; height: 44px; background: rgba(255,255,255,.15);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: #fff; flex-shrink: 0;
    }
    .cta-title { font-size: .95rem; font-weight: 700; color: #fff; }
    .cta-sub { font-size: .78rem; color: rgba(255,255,255,.7); margin-top: .15rem; }
    .btn-white {
        background: #fff; color: var(--blue-600); font-weight: 700;
        font-size: .82rem; padding: .55rem 1.1rem; border-radius: 8px;
        display: flex; align-items: center; gap: .35rem;
        text-decoration: none; white-space: nowrap; flex-shrink: 0;
        position: relative; z-index: 1; transition: opacity .15s;
    }
    .btn-white:hover { opacity: .88; }

    /* ── Stats ── */
    .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; }
    .stat-card {
        background: #fff; border: 1px solid var(--gray-200);
        border-radius: var(--radius); padding: 1.1rem 1.25rem;
        box-shadow: var(--shadow-sm); transition: box-shadow .2s;
    }
    .stat-card:hover { box-shadow: var(--shadow-md); }
    .stat-label {
        font-size: .75rem; color: var(--gray-400); font-weight: 500;
        display: flex; align-items: center; gap: .35rem; margin-bottom: .5rem;
    }
    .stat-label i { font-size: 14px; }
    .stat-value { font-size: 1.7rem; font-weight: 800; color: var(--blue-600); line-height: 1; }
    .stat-sub { font-size: .72rem; color: var(--gray-400); margin-top: .3rem; }
    .stat-sub .warn { color: var(--amber); font-weight: 600; }

    /* ── 2 colunas ── */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    /* ── Card ── */
    .card {
        background: #fff; border: 1px solid var(--gray-200);
        border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden;
    }
    .card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: .9rem 1.25rem; border-bottom: 1px solid var(--gray-100);
    }
    .card-title { font-size: .88rem; font-weight: 700; }
    .card-link {
        font-size: .75rem; color: var(--blue-600); text-decoration: none;
        display: flex; align-items: center; gap: .2rem;
    }
    .card-link:hover { text-decoration: underline; }

    /* ── Vagas ── */
    .vaga-item {
        display: flex; align-items: center; gap: .85rem;
        padding: .75rem 1.25rem; border-bottom: 1px solid var(--gray-100);
        cursor: pointer; transition: background .12s;
        text-decoration: none; color: inherit;
    }
    .vaga-item:last-child { border-bottom: none; }
    .vaga-item:hover { background: var(--gray-50); }
    .vaga-ico {
        width: 34px; height: 34px; background: var(--blue-50);
        border: 1px solid var(--blue-100); border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; color: var(--blue-600); flex-shrink: 0;
    }
    .vaga-info { flex: 1; min-width: 0; }
    .vaga-title { font-size: .83rem; font-weight: 600; margin-bottom: .1rem; }
    .vaga-meta { font-size: .72rem; color: var(--gray-400); display: flex; gap: .6rem; align-items: center; }
    .vaga-meta i { font-size: 12px; }

    /* ── Badges ── */
    .badge {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: .68rem; font-weight: 600;
        padding: .22rem .6rem; border-radius: 20px; white-space: nowrap;
    }
    .badge i { font-size: 11px; }
    .badge-green { background: #eaf6f0; color: #0f6e44; border: 1px solid #b6e0cc; }
    .badge-amber { background: #fef3e2; color: #92520a; border: 1px solid #f7d18a; }
    .badge-blue  { background: var(--blue-50); color: var(--blue-600); border: 1px solid var(--blue-100); }
    .badge-gray  { background: var(--gray-100); color: var(--gray-600); border: 1px solid var(--gray-200); }

    /* ── Candidatos ── */
    .cand-item {
        display: flex; align-items: center; gap: .85rem;
        padding: .75rem 1.25rem; border-bottom: 1px solid var(--gray-100);
        transition: background .12s; cursor: pointer;
        text-decoration: none; color: inherit;
    }
    .cand-item:last-child { border-bottom: none; }
    .cand-item:hover { background: var(--gray-50); }
    .cand-avatar {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .7rem; font-weight: 700; flex-shrink: 0;
    }
    .cand-info { flex: 1; min-width: 0; }
    .cand-name { font-size: .83rem; font-weight: 600; margin-bottom: .1rem; }
    .cand-vaga { font-size: .72rem; color: var(--gray-400); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cand-score { display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; }
    .score-num { font-size: .83rem; font-weight: 800; color: var(--blue-600); }
    .score-bar-wrap { width: 52px; height: 4px; background: var(--gray-200); border-radius: 2px; overflow: hidden; }
    .score-bar { height: 100%; border-radius: 2px; background: var(--blue-600); }

    /* ── Atividade ── */
    .atv-item {
        display: flex; gap: .85rem;
        padding: .75rem 1.25rem; border-bottom: 1px solid var(--gray-100);
    }
    .atv-item:last-child { border-bottom: none; }
    .atv-track { display: flex; flex-direction: column; align-items: center; padding-top: .25rem; }
    .atv-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .atv-line { width: 1px; flex: 1; background: var(--gray-200); margin: 3px 0; }
    .atv-body { flex: 1; min-width: 0; }
    .atv-text { font-size: .8rem; color: var(--gray-600); line-height: 1.5; }
    .atv-text strong { color: var(--gray-900); font-weight: 600; }
    .atv-time { font-size: .7rem; color: var(--gray-400); margin-top: .2rem; }

    /* ── Chat FAB ── */
    .chat-fab {
        position: fixed; bottom: 1.75rem; right: 1.75rem;
        width: 48px; height: 48px; background: var(--blue-600); border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; box-shadow: var(--shadow-blue);
        cursor: pointer; border: none; transition: transform .2s, opacity .15s; z-index: 50;
    }
    .chat-fab:hover { transform: scale(1.08); }

    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(2,1fr); }
        .two-col { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .dash-sidebar { display: none; }
        .dash-content { padding: 1rem; }
        .stats-grid { grid-template-columns: repeat(2,1fr); }
    }
</style>
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
            <a href="{{ route('dashboard') }}" class="sidebar-link active">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>
            <a href="#" class="sidebar-link">
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

            {{-- Cabeçalho --}}
            <div class="page-header">
                <div>
                    <h1>Dashboard</h1>
                    <p id="greeting-line">Carregando...</p>
                </div>
                <a href="#" class="btn btn-primary">
                    <i class="ti ti-plus" style="font-size:15px"></i> Nova vaga
                </a>
            </div>

            {{-- CTA Banner --}}
            <a href="#" class="cta-banner">
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
                        <a href="#" class="card-link">Ver todas <i class="ti ti-arrow-right" style="font-size:12px"></i></a>
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

        </main>
    </div>

    {{-- Chat FAB --}}
    <button class="chat-fab" title="Assistente de RH">
        <i class="ti ti-message"></i>
    </button>

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
