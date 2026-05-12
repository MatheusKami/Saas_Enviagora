@extends('onboarding.layout')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Contexto da empresa</h1>
    <p class="mt-1 text-gray-500">Essas informações vão direto pro nosso assistente de IA. Quanto mais detalhes, melhores serão as Job Descriptions e os matches.</p>
</div>

<form action="{{ route('onboarding.save', 4) }}" method="POST" class="space-y-6">
    @csrf

    {{-- Ritmo e modelo de trabalho --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h2 class="text-base font-semibold text-gray-900">Ambiente de trabalho</h2>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

            {{-- Ritmo de trabalho --}}
            <div>
                <label for="ritmo_trabalho" class="block text-sm font-medium text-gray-700 mb-1">
                    Ritmo de trabalho <span class="text-red-500">*</span>
                </label>
                <select id="ritmo_trabalho"
                        name="ritmo_trabalho"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm @error('ritmo_trabalho') border-red-500 @enderror"
                        required>
                    <option value="">Selecione...</option>
                    @php
                        $ritmos = [
                            'startup-acelerada'   => 'Startup acelerada (alta velocidade)',
                            'crescimento-rapido'  => 'Em crescimento rápido',
                            'corporativo-estavel' => 'Corporativo estável',
                            'conservador'         => 'Conservador e metódico',
                            'sazonal'             => 'Sazonal (varia muito)',
                        ];
                    @endphp
                    @foreach($ritmos as $value => $label)
                        <option value="{{ $value }}"
                                {{ old('ritmo_trabalho', $company->ritmo_trabalho) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('ritmo_trabalho')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Modelo de trabalho --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Modelo de trabalho <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-3">
                    @foreach(['presencial' => '🏢 Presencial', 'remoto' => '🏠 Remoto', 'hibrido' => '🔄 Híbrido'] as $value => $label)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio"
                                   name="modelo_trabalho"
                                   value="{{ $value }}"
                                   class="peer sr-only"
                                   {{ old('modelo_trabalho', $company->modelo_trabalho) === $value ? 'checked' : '' }}
                                   required>
                            <div class="text-center py-3 px-2 border-2 border-gray-200 rounded-xl text-sm font-medium text-gray-600 transition-all
                                        peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700
                                        hover:border-gray-300">
                                {{ $label }}
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('modelo_trabalho')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Cultura da empresa --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Cultura da empresa</h2>
            <p class="text-sm text-gray-500 mt-0.5">Como é o dia a dia? Qual é o jeito de trabalhar da sua empresa?</p>
        </div>

        <div>
            <textarea id="cultura_empresa"
                      name="cultura_empresa"
                      rows="5"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm @error('cultura_empresa') border-red-500 @enderror"
                      placeholder="Ex: Somos uma empresa que valoriza autonomia e resultados. Temos reuniões curtas, comunicação direta e incentivamos a inovação constante. A liderança é acessível e os colaboradores têm voz ativa nas decisões..."
                      minlength="50"
                      required>{{ old('cultura_empresa', $company->cultura_empresa) }}</textarea>

            {{-- Contador de caracteres --}}
            <div class="flex justify-between mt-1">
                @error('cultura_empresa')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @else
                    <p class="text-xs text-gray-500">Mínimo 50 caracteres</p>
                @enderror
                <span id="cultura-count" class="text-xs text-gray-400">0/2000</span>
            </div>
        </div>
    </div>

    {{-- Valores e diferenciais --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900">Valores e diferenciais <span class="text-gray-400 font-normal text-sm">(opcional mas recomendado)</span></h2>

        <div>
            <label for="valores_empresa" class="block text-sm font-medium text-gray-700 mb-1">
                Valores da empresa
            </label>
            <textarea id="valores_empresa"
                      name="valores_empresa"
                      rows="3"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                      placeholder="Ex: Integridade, Inovação, Foco no cliente, Trabalho em equipe...">{{ old('valores_empresa', $company->valores_empresa) }}</textarea>
        </div>

        <div>
            <label for="diferenciais_empresa" class="block text-sm font-medium text-gray-700 mb-1">
                Diferenciais e benefícios
            </label>
            <textarea id="diferenciais_empresa"
                      name="diferenciais_empresa"
                      rows="3"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                      placeholder="Ex: Plano de saúde top, participação nos lucros, home office flexível, stock options, bolsa de estudos...">{{ old('diferenciais_empresa', $company->diferenciais_empresa) }}</textarea>
        </div>
    </div>

    {{-- Preview de como vai ficar o prompt do Groq --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-900">Como o RHMatch usa essas informações</p>
                <p class="text-sm text-amber-700 mt-1">
                    O assistente de IA (Groq) usa o contexto da sua empresa pra gerar Job Descriptions personalizadas,
                    avaliar o fit cultural dos candidatos e dar sugestões de perguntas para entrevistas.
                    Quanto mais rico o contexto, mais preciso fica.
                </p>
            </div>
        </div>
    </div>

    {{-- Navegação --}}
    <div class="flex justify-between">
        <a href="{{ route('onboarding.step', 3) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>

        <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
            🎉 Concluir configuração
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
// Contador de caracteres do campo de cultura
const culturaTextarea = document.getElementById('cultura_empresa');
const culturaCount = document.getElementById('cultura-count');

function atualizarContador() {
    const len = culturaTextarea.value.length;
    culturaCount.textContent = `${len}/2000`;
    culturaCount.className = len < 50
        ? 'text-xs text-red-500'    // Vermelho se ainda não bateu o mínimo
        : 'text-xs text-green-600'; // Verde quando tá ok
}

culturaTextarea.addEventListener('input', atualizarContador);
atualizarContador(); // Roda no carregamento se já tem valor salvo
</script>
@endpush
