<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RHMatch') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Tabler Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>

    {{-- ===================================================== --}}
    {{-- TOPBAR --}}
    {{-- ===================================================== --}}
    <header class="dash-topbar">

        <a href="{{ route('dashboard') }}" class="topbar-brand">
            <div class="topbar-brand-icon">
                <i class="ti ti-users"></i>
            </div>

            RHMatch
        </a>

        <div class="topbar-actions">

            {{-- Notificações --}}
            <a href="#" class="topbar-icon-btn" title="Notificações">
                <i class="ti ti-bell" style="font-size:17px"></i>
                <span class="notif-dot"></span>
            </a>

            {{-- Avatar --}}
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

                    {{-- Dados usuário --}}
                    <div class="topbar-dropdown-header">
                        <div class="dd-name">
                            {{ Auth::user()->name }}
                        </div>

                        <div class="dd-email">
                            {{ Auth::user()->email }}
                        </div>
                    </div>

                    {{-- Meu perfil --}}
                    <a
                        href="{{ route('empresa.configuracoes') }}"
                        @click="open = false"
                    >
                        <i class="ti ti-user"></i>
                        Meu perfil
                    </a>

                    {{-- Configurações --}}
                    <a
                        href="{{ route('empresa.configuracoes') }}"
                        @click="open = false"
                    >
                        <i class="ti ti-settings"></i>
                        Configurações
                    </a>

                    <div class="topbar-dropdown-divider"></div>

                    {{-- Logout --}}
                    <a
                        href="{{ route('logout') }}"
                        style="color:var(--red)"
                        @click.prevent="
                            open = false;
                            $nextTick(() => document.getElementById('logout-form-top').submit())
                        "
                    >
                        <i class="ti ti-logout"></i>
                        Sair
                    </a>

                </div>
            </div>

        </div>

        {{-- Form logout top --}}
        <form
            id="logout-form-top"
            action="{{ route('logout') }}"
            method="POST"
            style="display:none"
        >
            @csrf
        </form>

    </header>

    {{-- ===================================================== --}}
    {{-- LAYOUT --}}
    {{-- ===================================================== --}}
    <div class="dash-layout">

        {{-- ================================================= --}}
        {{-- SIDEBAR --}}
        {{-- ================================================= --}}
        <aside class="dash-sidebar">

            <span class="sidebar-section">
                Principal
            </span>

            {{-- Dashboard --}}
            <a
                href="{{ route('dashboard') }}"
                class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >
                <i class="ti ti-layout-dashboard"></i>
                Dashboard
            </a>

            {{-- Vagas --}}
            <a
                href="{{ route('jobs.index') }}"
                class="sidebar-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}"
            >
                <i class="ti ti-briefcase"></i>
                Vagas

                <span class="sidebar-badge">
                    4
                </span>
            </a>

            {{-- Candidatos --}}
            <a
                href="{{ route('candidates.index') }}"
                class="sidebar-link {{ request()->routeIs('candidates.*') ? 'active' : '' }}"
            >
                <i class="ti ti-users"></i>
                Candidatos
            </a>

            <span class="sidebar-section">
                Empresa
            </span>

            {{-- Organograma --}}
            <a
                href="#"
                class="sidebar-link"
            >
                <i class="ti ti-sitemap"></i>
                Organograma
            </a>

            {{-- Testes --}}
            <a
                href="#"
                class="sidebar-link"
            >
                <i class="ti ti-brain"></i>
                Testes
            </a>

            {{-- Configurações --}}
            <a
                href="{{ route('empresa.configuracoes') }}"
                class="sidebar-link {{ request()->routeIs('empresa.configuracoes*') ? 'active' : '' }}"
            >
                <i class="ti ti-settings"></i>
                Configurações
            </a>

            <div class="sidebar-spacer"></div>

            {{-- Logout --}}
            <a
                href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form-side').submit();"
                class="sidebar-link"
                style="color:var(--red)"
            >
                <i class="ti ti-logout"></i>
                Sair
            </a>

            <form
                id="logout-form-side"
                action="{{ route('logout') }}"
                method="POST"
                style="display:none"
            >
                @csrf
            </form>

        </aside>

        {{-- ================================================= --}}
        {{-- CONTEÚDO --}}
        {{-- ================================================= --}}
        <main class="dash-content">
            {{ $slot }}
        </main>

    </div>

    {{-- ===================================================== --}}
    {{-- CHAT FAB --}}
    {{-- ===================================================== --}}
    <a
        href="{{ route('ia.chat') }}"
        class="chat-fab"
        title="Assistente de RH"
    >
        <i class="ti ti-message"></i>
    </a>

    @stack('scripts')

</body>
</html>