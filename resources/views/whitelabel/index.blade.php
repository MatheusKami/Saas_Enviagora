<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vagas em {{ $company->display_name }}</title>

    {{-- Favicon: uso a logo da empresa se tiver --}}
    @if($company->logo_url)
        <link rel="icon" type="image/png" href="{{ $company->logo_url }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-full">

    {{-- Header do portal — mostra logo e nome da empresa (white-label) --}}
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">

            {{-- Logo + nome da empresa --}}
            <div class="flex items-center gap-3">
                {{-- Uso o componente que criei pra mostrar logo ou iniciais --}}
                <x-company-logo :company="$company" size="md" />
                <div>
                    <span class="font-bold text-gray-900 text-lg">{{ $company->display_name }}</span>
                    @if($company->website)
                        <a href="{{ $company->website }}"
                           target="_blank"
                           class="block text-xs text-gray-400 hover:text-indigo-600 transition-colors">
                            {{ parse_url($company->website, PHP_URL_HOST) }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Badge "Powered by RHMatch" discreto --}}
            <span class="text-xs text-gray-400">Powered by RHMatch</span>
        </div>
    </header>

    {{-- Hero da empresa --}}
    <section class="bg-white border-b border-gray-100">
        <div class="max-w-5xl mx-auto px-6 py-12 text-center">
            <h1 class="text-3xl font-bold text-gray-900">
                Trabalhe na {{ $company->display_name }}
            </h1>

            @if($company->cultura_empresa)
                <p class="mt-3 text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
                    {{ Str::limit($company->cultura_empresa, 200) }}
                </p>
            @endif

            {{-- Info rápida: modelo de trabalho, localização --}}
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if($company->modelo_trabalho)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-sm font-medium">
                        @switch($company->modelo_trabalho)
                            @case('presencial') 🏢 Presencial @break
                            @case('remoto')     🏠 100% Remoto @break
                            @case('hibrido')    🔄 Híbrido @break
                        @endswitch
                    </span>
                @endif

                @if($company->cidade && $company->estado)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-700 rounded-full text-sm">
                        📍 {{ $company->cidade }}, {{ $company->estado }}
                    </span>
                @endif

                @if($company->ritmo_trabalho)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 rounded-full text-sm">
                        ⚡ {{ ucfirst(str_replace('-', ' ', $company->ritmo_trabalho)) }}
                    </span>
                @endif
            </div>
        </div>
    </section>

    {{-- Lista de vagas abertas --}}
    <main class="max-w-5xl mx-auto px-6 py-10">

        {{-- Mensagens de sucesso --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-green-800 text-sm font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">
                Vagas abertas
                <span class="ml-2 text-sm font-normal text-gray-500">({{ $vagas->total() }} {{ $vagas->total() === 1 ? 'vaga' : 'vagas' }})</span>
            </h2>
        </div>

        @if($vagas->isEmpty())
            {{-- Sem vagas abertas --}}
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900">Nenhuma vaga aberta no momento</h3>
                <p class="text-gray-500 mt-1">Volte em breve — novas oportunidades aparecem frequentemente.</p>
            </div>
        @else
            {{-- Grid de cards de vagas --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($vagas as $vaga)
                    <a href="{{ route('whitelabel.vaga', [$company->subdomain, $vaga]) }}"
                       class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all duration-200">

                        {{-- Área e nível --}}
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">
                                {{ $vaga->area }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $vaga->nivel }}</span>
                        </div>

                        {{-- Título da vaga --}}
                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700 transition-colors">
                            {{ $vaga->titulo }}
                        </h3>

                        {{-- Descrição curta --}}
                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                            {{ Str::limit(strip_tags($vaga->descricao), 100) }}
                        </p>

                        {{-- Info: modelo e localização --}}
                        <div class="mt-4 flex items-center gap-3 text-xs text-gray-500">
                            <span>{{ ucfirst($vaga->modelo_trabalho ?? $company->modelo_trabalho) }}</span>
                            @if($vaga->cidade || $company->cidade)
                                <span>•</span>
                                <span>{{ $vaga->cidade ?? $company->cidade }}</span>
                            @endif
                        </div>

                        {{-- Faixa salarial (se estiver configurado pra exibir) --}}
                        @if($vaga->exibir_salario && $vaga->salario_min)
                            <div class="mt-3 text-sm font-medium text-green-700">
                                R$ {{ number_format($vaga->salario_min, 0, ',', '.') }}
                                @if($vaga->salario_max) — R$ {{ number_format($vaga->salario_max, 0, ',', '.') }} @endif
                                /mês
                            </div>
                        @endif

                        {{-- CTA --}}
                        <div class="mt-4 text-sm font-medium text-indigo-600 group-hover:text-indigo-700 flex items-center gap-1">
                            Ver vaga
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Paginação --}}
            @if($vagas->hasPages())
                <div class="mt-8">
                    {{ $vagas->links() }}
                </div>
            @endif
        @endif
    </main>

    {{-- Footer simples --}}
    <footer class="mt-16 border-t border-gray-200 bg-white py-6">
        <div class="max-w-5xl mx-auto px-6 text-center">
            <p class="text-sm text-gray-400">
                Portal de vagas de <strong class="text-gray-600">{{ $company->display_name }}</strong>
                · Powered by <a href="#" class="text-indigo-600 hover:text-indigo-700">RHMatch</a>
            </p>
        </div>
    </footer>

</body>
</html>
