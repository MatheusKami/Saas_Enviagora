<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RHMatch — Configuração da empresa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

{{-- Container principal --}}
<div class="min-h-full flex flex-col">

    {{-- Header com logo do RHMatch e nome da empresa --}}
    <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="max-w-3xl mx-auto flex items-center justify-between">

            {{-- Logo do RHMatch (sistema) --}}
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">RH</span>
                </div>
                <span class="font-semibold text-gray-900">RHMatch</span>
            </div>

            {{-- Usuário logado --}}
            <span class="text-sm text-gray-500">{{ Auth::user()->name }}</span>
        </div>
    </header>

    {{-- Barra de progresso das 4 etapas --}}
    <div class="bg-white border-b border-gray-100 px-6 py-4">
        <div class="max-w-3xl mx-auto">

            {{-- Labels das etapas --}}
            <div class="flex items-center justify-between mb-2">
                @php
                    $steps = [
                        1 => 'Dados cadastrais',
                        2 => 'Organograma',
                        3 => 'Colaboradores',
                        4 => 'Contexto',
                    ];
                @endphp

                @foreach($steps as $num => $label)
                    <div class="flex flex-col items-center gap-1 flex-1">
                        {{-- Círculo da etapa --}}
                        <div @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all',
                            'bg-indigo-600 text-white'              => $step >= $num,
                            'bg-gray-200 text-gray-500'             => $step < $num,
                            'ring-4 ring-indigo-200'                => $step == $num,
                        ])>
                            @if($step > $num)
                                {{-- Checkmark pras etapas já concluídas --}}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        {{-- Label --}}
                        <span @class([
                            'text-xs font-medium hidden sm:block',
                            'text-indigo-600' => $step >= $num,
                            'text-gray-400'   => $step < $num,
                        ])>{{ $label }}</span>
                    </div>

                    {{-- Linha conectora entre etapas --}}
                    @if($num < 4)
                        <div @class([
                            'flex-1 h-0.5 -mt-6 mx-1 transition-all',
                            'bg-indigo-600' => $step > $num,
                            'bg-gray-200'   => $step <= $num,
                        ])></div>
                    @endif
                @endforeach
            </div>

        </div>
    </div>

    {{-- Mensagens de feedback (sucesso/erro) --}}
    @if(session('success'))
        <div class="max-w-3xl mx-auto w-full px-6 pt-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-green-800 text-sm">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-3xl mx-auto w-full px-6 pt-4">
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="text-red-800 text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Conteúdo da etapa atual --}}
    <main class="flex-1 px-6 py-8">
        <div class="max-w-3xl mx-auto">
            @yield('content')
        </div>
    </main>

</div>

@stack('scripts')
</body>
</html>
