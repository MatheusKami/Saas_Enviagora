{{--
    Componente: x-company-logo
    Uso: <x-company-logo :company="$company" size="md" />
    Mostra a logo da empresa ou um placeholder com as iniciais se não tiver logo

    Props:
    - company: model Company
    - size: xs | sm | md | lg | xl (default: md)
    - class: classes CSS adicionais
--}}

@props([
    'company',
    'size'  => 'md',
    'class' => '',
])

@php
    // Mapas de tamanho pra classes Tailwind
    $sizeClasses = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-14 h-14 text-lg',
        'xl' => 'w-20 h-20 text-2xl',
    ];

    $containerClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Pego as iniciais do nome pra usar no placeholder
    $nome = $company->display_name ?? 'E';
    $iniciais = collect(explode(' ', $nome))
        ->take(2)
        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp

@if($company->logo_url)
    {{-- Tem logo: mostro a imagem --}}
    <img src="{{ $company->logo_url }}"
         alt="Logo {{ $company->display_name }}"
         {{ $attributes->merge(['class' => "rounded-lg object-contain {$containerClass} {$class}"]) }}>
@else
    {{-- Sem logo: mostro as iniciais com fundo colorido --}}
    <div {{ $attributes->merge(['class' => "rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white {$containerClass} {$class}"]) }}>
        {{ $iniciais }}
    </div>
@endif
