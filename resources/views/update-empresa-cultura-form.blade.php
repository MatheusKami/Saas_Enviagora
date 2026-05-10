<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Perfil & Cultura') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Defina o contexto, ritmo e valores que guiam a sua empresa.') }}
        </p>
    </header>

    {{-- Mensagem de sucesso --}}
    @if (session('status') === 'empresa-cultura-updated')
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 3000)"
            class="mt-4 text-sm text-green-600"
        >
            {{ __('Cultura salva com sucesso.') }}
        </div>
    @endif

    <form
        id="form-empresa-cultura"
        x-data="empresaCulturaForm()"
        @submit.prevent="submit"
        class="mt-6 space-y-6"
    >
        {{-- Contexto --}}
        <div>
            <x-input-label for="contexto_empresa" :value="__('Contexto atual da empresa')" />
            <textarea
                id="contexto_empresa"
                name="contexto_empresa"
                rows="4"
                x-model="form.contexto_empresa"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                placeholder="{{ __('Estamos em fase de crescimento acelerado...') }}"
            ></textarea>
            <x-input-error class="mt-2" :messages="$errors->get('contexto_empresa')" />
        </div>

        {{-- Perfil/Ritmo --}}
        <div>
            <x-input-label for="perfil_ritmo" :value="__('Perfil / Ritmo da empresa')" />
            <select
                id="perfil_ritmo"
                name="perfil_ritmo"
                x-model="form.perfil_ritmo"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
                <option value="">{{ __('Selecione...') }}</option>
                <option value="dinamico">{{ __('Dinâmico / Ágil') }}</option>
                <option value="analitico">{{ __('Analítico / Estruturado') }}</option>
                <option value="equilibrado">{{ __('Equilibrado') }}</option>
                <option value="criativo">{{ __('Criativo / Inovador') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('perfil_ritmo')" />
        </div>

        {{-- Valores --}}
        <div>
            <x-input-label for="valores_text" :value="__('Valores da empresa')" />
            <p class="text-xs text-gray-400 mb-1">{{ __('Um valor por linha.') }}</p>
            <textarea
                id="valores_text"
                rows="4"
                x-model="form.valores_text"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                placeholder="Inovação&#10;Colaboração&#10;Resultados"
            ></textarea>
            {{-- valores[] é montado no submit() e não vem direto do textarea --}}
        </div>

        {{-- Erro inline --}}
        <p x-show="errorMessage" x-text="errorMessage" class="text-sm text-red-600"></p>

        {{-- Botão --}}
        <div class="flex items-center gap-4">
            <x-primary-button x-bind:disabled="loading">
                <span x-show="!loading">{{ __('Salvar') }}</span>
                <span x-show="loading">{{ __('Salvando...') }}</span>
            </x-primary-button>
        </div>
    </form>
</section>

@push('scripts')
<script>
function empresaCulturaForm() {
    return {
        loading: false,
        errorMessage: '',

        form: {
            contexto_empresa: @json(old('contexto_empresa', $company->contexto_empresa ?? '')),
            perfil_ritmo:     @json(old('perfil_ritmo', $company->perfil_ritmo ?? '')),

            // Converte o array do banco → string "um valor por linha" para exibição
            valores_text: @json(
                old('valores_text',
                    is_array($company->valores ?? null)
                        ? implode("\n", $company->valores)
                        : ''
                )
            ),
        },

        async submit() {
            this.loading      = true;
            this.errorMessage = '';

            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('contexto_empresa', this.form.contexto_empresa ?? '');
            formData.append('perfil_ritmo',     this.form.perfil_ritmo     ?? '');

            // Converte valores_text → valores[] para o Laravel
            if (this.form.valores_text.trim()) {
                this.form.valores_text
                    .split('\n')
                    .map(v => v.trim())
                    .filter(Boolean)
                    .forEach(v => formData.append('valores[]', v));
            }

            try {
                const response = await fetch('/editar_empresa/cultura', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    const mensagens = data.errors
                        ? Object.values(data.errors).flat().join('\n')
                        : (data.message || 'Erro desconhecido.');
                    throw new Error(mensagens);
                }

                window.location.href = data.redirect;

            } catch (error) {
                console.error('[Empresa - Cultura] Erro:', error);
                this.errorMessage = error.message;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
