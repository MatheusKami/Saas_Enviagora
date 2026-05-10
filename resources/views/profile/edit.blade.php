<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minha Empresa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-10">

                    {{-- Dados básicos da empresa --}}
                    @include('empresa.partials.update-empresa-information-form', ['company' => $company])

                    {{-- Perfil e cultura --}}
                    @include('empresa.partials.update-empresa-cultura-form', ['company' => $company])

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
