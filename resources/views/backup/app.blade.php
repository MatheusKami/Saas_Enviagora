<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RHMatch') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/style.css">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    @stack('styles')
</head>
<body>

    {{-- ── Topbar global ── --}}
    <header class="dash-topbar">
        <a href="{{ route('dashboard') }}" class="topbar-brand">
            <div class="topbar-brand-icon"><i class="ti ti-users"></i></div>
            RHMatch
        </a>

        <div class="topbar-actions">

            {{-- Notificações --}}
            <a href="#" class="topbar-icon-btn" title="Notificações">
                <i class="ti ti-bell" style="font-size:17px"></i>
                <span class="notif-dot"></span>
            </a>

            {{-- Avatar + dropdown — controlado pelo Alpine.js --}}
            <div
                x-data="{ open: false }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                style="position:relative"
            >
                {{-- Botão avatar --}}
                <div
                    class="topbar-avatar"
                    @click.stop="open = !open"
                    :aria-expanded="open.toString()"
                    role="button"
                    title="{{ Auth::user()->name }}"
                >
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>

                {{-- Dropdown --}}
                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="topbar-dropdown"
                    style="transform-origin:top right"
                >
                    {{-- Cabeçalho com nome e e-mail --}}
                    <div class="topbar-dropdown-header">
                        <div class="dd-name">{{ Auth::user()->name }}</div>
                        <div class="dd-email">{{ Auth::user()->email }}</div>
                    </div>

                    {{-- Meu perfil → profile.edit (dados pessoais do usuário) --}}
                    <a href="{{ route('profile.edit') }}" @click="open = false">
                        <i class="ti ti-user"></i> Meu perfil
                    </a>

                    {{-- Empresa → empresa.edit (dados da empresa) --}}
                    <a href="{{ route('empresa.edit') }}" @click="open = false">
                        <i class="ti ti-building"></i> Minha empresa
                    </a>

                    <div class="topbar-dropdown-divider"></div>

                    <a
                        href="{{ route('logout') }}"
                        style="color:var(--red)"
                        @click.prevent="open = false; $nextTick(() => document.getElementById('logout-form-top').submit())"
                    >
                        <i class="ti ti-logout"></i> Sair
                    </a>
                </div>
            </div>

        </div>

        <form id="logout-form-top" action="{{ route('logout') }}" method="POST" style="display:none">
            @csrf
        </form>
    </header>

    <div class="dash-layout">

        {{-- ── Sidebar global ── --}}
        <aside class="dash-sidebar">

            {{-- ── Principal ── --}}
            <span class="sidebar-section">Principal</span>

            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>

            <a href="{{ route('vagas.index') }}"
               class="sidebar-link {{ request()->routeIs('vagas.*') ? 'active' : '' }}">
                <i class="ti ti-briefcase"></i> Vagas
                <span class="sidebar-badge">4</span>
            </a>

            <a href="#"
               class="sidebar-link {{ request()->routeIs('candidatos.*') ? 'active' : '' }}">
                <i class="ti ti-users"></i> Candidatos
            </a>

            {{-- ── Empresa ── --}}
            <span class="sidebar-section">Empresa</span>

            <a href="#"
               class="sidebar-link {{ request()->routeIs('organograma.*') ? 'active' : '' }}">
                <i class="ti ti-sitemap"></i> Organograma
            </a>

            <a href="#"
               class="sidebar-link {{ request()->routeIs('testes.*') ? 'active' : '' }}">
                <i class="ti ti-brain"></i> Testes
            </a>

            {{-- Dados da empresa (onboarding/edição) --}}
            <a href="{{ route('empresa.edit') }}"
               class="sidebar-link {{ request()->routeIs('empresa.*') ? 'active' : '' }}">
                <i class="ti ti-building"></i> Minha empresa
            </a>

            {{-- ── Conta ── --}}
            <span class="sidebar-section">Conta</span>

            {{-- Perfil pessoal do usuário --}}
            <a href="{{ route('profile.edit') }}"
               class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="ti ti-user"></i> Meu perfil
            </a>

            <div class="sidebar-spacer"></div>

            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-side').submit();"
               class="sidebar-link" style="color:var(--red)">
                <i class="ti ti-logout"></i> Sair
            </a>

            <form id="logout-form-side" action="{{ route('logout') }}" method="POST" style="display:none">
                @csrf
            </form>

        </aside>

        {{-- ── Conteúdo principal ── --}}
        <main class="dash-content">
            {{ $slot }}
        </main>

    </div>

    {{-- Chat FAB global --}}
    <a href="/chat" class="chat-fab" title="Assistente de RH">
        <i class="ti ti-message"></i>
    </a>

    @stack('scripts')

</body>
</html>
