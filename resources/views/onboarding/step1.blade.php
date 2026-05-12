@extends('onboarding.layout')

@section('content')

{{-- Cabeçalho da etapa --}}
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dados da sua empresa</h1>
    <p class="mt-1 text-gray-500">Essas informações vão aparecer no portal white-label dos candidatos.</p>
</div>

<form action="{{ route('onboarding.save', 1) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    {{-- UPLOAD DA LOGO --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Logo da empresa</h2>

        <div class="flex items-start gap-6">

            {{-- Preview da logo atual (ou placeholder) --}}
            <div class="flex-shrink-0">
                <div id="logo-preview-wrapper"
                     class="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">

                    @if($company->logo_url)
                        {{-- Já tem logo — mostro ela aqui --}}
                        <img id="logo-preview"
                             src="{{ $company->logo_url }}"
                             alt="Logo atual"
                             class="w-full h-full object-contain p-1">
                    @else
                        {{-- Placeholder quando não tem logo --}}
                        <div id="logo-placeholder" class="flex flex-col items-center text-gray-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs mt-1">Logo</span>
                        </div>
                        {{-- Preview vai aparecer aqui quando selecionar arquivo --}}
                        <img id="logo-preview" src="" alt="" class="w-full h-full object-contain p-1 hidden">
                    @endif
                </div>
            </div>

            {{-- Controles do upload --}}
            <div class="flex-1">
                <label for="logo"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    {{ $company->logo_url ? 'Trocar logo' : 'Enviar logo' }}
                </label>

                {{-- Input escondido — o label acima faz o clique --}}
                <input type="file"
                       id="logo"
                       name="logo"
                       accept="image/jpeg,image/png,image/webp"
                       class="sr-only"
                       onchange="previewLogo(this)">

                <p class="mt-2 text-xs text-gray-500">JPG, PNG ou WebP. Máximo 2MB.</p>

                {{-- Nome do arquivo selecionado --}}
                <p id="logo-filename" class="mt-1 text-xs text-indigo-600 hidden"></p>

                @error('logo')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- DADOS PRINCIPAIS --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900">Identificação</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Razão Social --}}
            <div class="sm:col-span-2">
                <label for="razao_social" class="block text-sm font-medium text-gray-700 mb-1">
                    Razão Social <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="razao_social"
                       name="razao_social"
                       value="{{ old('razao_social', $company->razao_social) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm @error('razao_social') border-red-500 @enderror"
                       placeholder="Ex: Empresa Exemplo LTDA"
                       required>
                @error('razao_social')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nome Fantasia --}}
            <div>
                <label for="nome_fantasia" class="block text-sm font-medium text-gray-700 mb-1">
                    Nome Fantasia
                </label>
                <input type="text"
                       id="nome_fantasia"
                       name="nome_fantasia"
                       value="{{ old('nome_fantasia', $company->nome_fantasia) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="Ex: Empresa Exemplo">
            </div>

            {{-- CNPJ --}}
            <div>
                <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">
                    CNPJ <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="cnpj"
                       name="cnpj"
                       value="{{ old('cnpj', $company->cnpj) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="00.000.000/0000-00"
                       maxlength="18"
                       required>
                @error('cnpj')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- E-mail --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email', $company->email) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="rh@empresa.com.br">
            </div>

            {{-- Telefone --}}
            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input type="text"
                       id="telefone"
                       name="telefone"
                       value="{{ old('telefone', $company->telefone) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="(11) 99999-9999">
            </div>

            {{-- Website --}}
            <div>
                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                <input type="url"
                       id="website"
                       name="website"
                       value="{{ old('website', $company->website) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="https://empresa.com.br">
            </div>

            {{-- Subdomínio white-label --}}
            <div class="sm:col-span-2">
                <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-1">
                    Subdomínio do portal de vagas
                </label>
                <div class="flex rounded-lg shadow-sm">
                    <input type="text"
                           id="subdomain"
                           name="subdomain"
                           value="{{ old('subdomain', $company->subdomain) }}"
                           class="flex-1 min-w-0 rounded-l-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           placeholder="minhaempresa">
                    <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        .rhmatch.com.br
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Só letras, números e hífens. Candidatos vão acessar pelo link acima.</p>
                @error('subdomain')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- ENDEREÇO (ViaCEP) --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900">Endereço</h2>
        <p class="text-sm text-gray-500">Digite o CEP e preencheremos automaticamente.</p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
            {{-- CEP com busca automática --}}
            <div class="sm:col-span-2">
                <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                <div class="relative">
                    <input type="text"
                           id="cep"
                           name="cep"
                           value="{{ old('cep', $company->cep) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           placeholder="00000-000"
                           maxlength="9"
                           onblur="buscarCep(this.value)">
                    {{-- Spinner de carregamento do ViaCEP --}}
                    <div id="cep-loading" class="absolute right-2 top-2 hidden">
                        <svg class="animate-spin w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Logradouro --}}
            <div class="sm:col-span-4">
                <label for="logradouro" class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                <input type="text" id="logradouro" name="logradouro"
                       value="{{ old('logradouro', $company->logradouro) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="Rua, Avenida...">
            </div>

            {{-- Número --}}
            <div class="sm:col-span-1">
                <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                <input type="text" id="numero" name="numero"
                       value="{{ old('numero', $company->numero) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="123">
            </div>

            {{-- Complemento --}}
            <div class="sm:col-span-2">
                <label for="complemento" class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                <input type="text" id="complemento" name="complemento"
                       value="{{ old('complemento', $company->complemento) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="Sala 101">
            </div>

            {{-- Bairro --}}
            <div class="sm:col-span-3">
                <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                <input type="text" id="bairro" name="bairro"
                       value="{{ old('bairro', $company->bairro) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            {{-- Cidade --}}
            <div class="sm:col-span-4">
                <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                <input type="text" id="cidade" name="cidade"
                       value="{{ old('cidade', $company->cidade) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            {{-- Estado --}}
            <div class="sm:col-span-2">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <input type="text" id="estado" name="estado"
                       value="{{ old('estado', $company->estado) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       maxlength="2" placeholder="SP">
            </div>
        </div>
    </div>

    {{-- Botão de avançar --}}
    <div class="flex justify-end">
        <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
            Salvar e continuar
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
// =========================================================
// Preview da logo antes de fazer upload
// Mostro a imagem escolhida em tempo real pra o usuário ver
// =========================================================
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        const preview = document.getElementById('logo-preview');
        const placeholder = document.getElementById('logo-placeholder');
        const filename = document.getElementById('logo-filename');

        // Mostro a preview e escondo o placeholder
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');

        // Mostro o nome do arquivo selecionado
        filename.textContent = '✓ ' + file.name;
        filename.classList.remove('hidden');
    };

    reader.readAsDataURL(file);
}

// =========================================================
// Busca de CEP via ViaCEP
// Preenche os campos de endereço automaticamente
// =========================================================
async function buscarCep(cep) {
    // Remove máscara e espaços
    const cepLimpo = cep.replace(/\D/g, '');
    if (cepLimpo.length !== 8) return;

    // Mostra loading
    document.getElementById('cep-loading').classList.remove('hidden');

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
        const data = await response.json();

        if (!data.erro) {
            // Preencho os campos com os dados da API
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value      = data.bairro    || '';
            document.getElementById('cidade').value      = data.localidade || '';
            document.getElementById('estado').value      = data.uf        || '';

            // Foco no campo número pra facilitar
            document.getElementById('numero').focus();
        } else {
            alert('CEP não encontrado. Verifique e tente novamente.');
        }
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
    } finally {
        document.getElementById('cep-loading').classList.add('hidden');
    }
}

// Máscara de CNPJ
document.getElementById('cnpj').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    e.target.value = v;
});

// Máscara de CEP
document.getElementById('cep').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    e.target.value = v;
});
</script>
@endpush
