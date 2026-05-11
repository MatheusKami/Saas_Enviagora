<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurações da Conta e Empresa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-10">

                    {{-- Dados do Usuário --}}
                    {{-- Formulário de dados do usuário --}}
                    @include('profile.partials.update-profile-information-form')

                    {{-- Formulários da empresa (os que você já tinha) --}}
                    @include('empresa.partials.update-company-information-form', ['company' => $company])
                    @include('empresa.partials.update-company-culture-form', ['company' => $company])

                    {{-- Dados da Empresa --}}
                    @if (isset($company) && $company)
                        @include('update-empresa-information-form', ['company' => $company])

                        {{-- Perfil e cultura --}}
                        @include('update-empresa-cultura-form', ['company' => $company])
                    @else
                        <div class="p-4 bg-yellow-100 text-yellow-700 rounded">
                            Cadastre sua empresa no onboarding primeiro.
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>