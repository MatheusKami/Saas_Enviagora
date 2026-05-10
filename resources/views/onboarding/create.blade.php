<x-app-layout>
    <div class="max-w-2xl mx-auto py-12">

        {{-- Cabeçalho da página --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold">Bem-vindo ao RHMatch!</h1>
            <p class="text-gray-600 mt-2">Vamos configurar sua empresa em poucos passos</p>
        </div>

        {{-- Wizard controlado pelo Alpine.js --}}
        <div class="bg-white rounded-2xl shadow-sm border p-8" x-data="onboardingWizard()">

            {{-- Indicador de etapas --}}
            <div class="flex justify-between mb-8">
                <div @click="step = 1"
                     :class="{ 'text-blue-600 font-semibold': step === 1 }"
                     class="cursor-pointer flex-1 text-center">
                    1. Dados da Empresa
                </div>
                <div @click="step = 2"
                     :class="{ 'text-blue-600 font-semibold': step === 2 }"
                     class="cursor-pointer flex-1 text-center">
                    2. Perfil &amp; Cultura
                </div>
                <div class="flex-1 text-center text-gray-400">
                    3. Organograma (em breve)
                </div>
            </div>

            {{--
                Obs: usamos fetch() para enviar os dados como FormData (necessário
                para o upload do logo). Por isso o form NÃO usa action/method
                tradicionais — o @submit.prevent chama submit() no Alpine.
                O @csrf blade não é necessário aqui; o token é lido via meta tag
                e enviado manualmente no header X-CSRF-TOKEN do fetch.
            --}}
            <form @submit.prevent="submit" enctype="multipart/form-data">

                {{-- ── Etapa 1: Dados da empresa ───────────────────────── --}}
                <div x-show="step === 1">
                    <div class="space-y-6">

                        <div>
                            <label>Razão Social <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.razao_social" required class="w-full input">
                        </div>

                        <div>
                            <label>CNPJ</label>
                            <input type="text" x-model="form.cnpj"
                                   placeholder="00.000.000/0000-00" class="w-full input">
                        </div>

                        <div>
                            <label>Endereço completo</label>
                            <textarea x-model="form.endereco_completo" rows="2" class="w-full input"></textarea>
                        </div>

                        <div>
                            <label>Logo da empresa</label>
                            {{-- O arquivo é capturado via handleLogo() e enviado no FormData --}}
                            <input type="file" @change="handleLogo($event)" accept="image/*" class="w-full">
                        </div>

                        <div>
                            <label>URL da empresa (site)</label>
                            <input type="url" x-model="form.url_empresa"
                                   placeholder="https://" class="w-full input">
                        </div>

                    </div>
                </div>

                {{-- ── Etapa 2: Perfil e cultura ────────────────────────── --}}
                <div x-show="step === 2">
                    <div class="space-y-6">

                        <div>
                            <label>Contexto atual da empresa</label>
                            <textarea x-model="form.contexto_empresa" rows="4"
                                      placeholder="Estamos em fase de crescimento acelerado..."
                                      class="w-full input"></textarea>
                        </div>

                        <div>
                            <label>Perfil/Ritmo da empresa</label>
                            <select x-model="form.perfil_ritmo" class="w-full input">
                                <option value="">Selecione...</option>
                                <option value="dinamico">Dinâmico / Ágil</option>
                                <option value="analitico">Analítico / Estruturado</option>
                                <option value="equilibrado">Equilibrado</option>
                                <option value="criativo">Criativo / Inovador</option>
                            </select>
                        </div>

                        <div>
                            <label>Valores da empresa (um por linha)</label>
                            {{--
                                O usuário digita um valor por linha.
                                No submit() convertemos para um array e enviamos
                                como valores[] para o Laravel.
                            --}}
                            <textarea x-model="form.valores_text" rows="4"
                                      placeholder="Inovação&#10;Colaboração&#10;Resultados"
                                      class="w-full input"></textarea>
                        </div>

                    </div>
                </div>

                {{-- ── Navegação entre etapas ───────────────────────────── --}}
                <div class="flex justify-between mt-10">

                    {{-- Voltar — só aparece a partir da etapa 2 --}}
                    <button type="button" @click="previousStep"
                            x-show="step > 1"
                            class="btn-secondary">← Voltar</button>

                    {{-- Próximo — aparece nas etapas 1 e 2 --}}
                    <button type="button" @click="nextStep"
                            x-show="step < 2"
                            class="btn-primary ml-auto">Próximo →</button>

                    {{-- Finalizar — aparece somente na etapa 2 (etapa 3 ainda não existe) --}}
                    <button type="submit"
                            x-show="step === 2"
                            :disabled="loading"
                            class="btn-primary ml-auto">
                        <span x-show="!loading">Finalizar Cadastro</span>
                        <span x-show="loading">Salvando...</span>
                    </button>

                </div>

                {{-- Mensagem de erro exibida abaixo dos botões --}}
                <p x-show="errorMessage" x-text="errorMessage"
                   class="text-red-500 text-sm mt-4"></p>

            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    function onboardingWizard() {
        return {
            step: 1,
            loading: false,
            form: {
                razao_social: '',
                cnpj: '',
                endereco_completo: '',
                url_empresa: '',
                contexto_empresa: '',
                perfil_ritmo: '',
                valores_text: '',
            },
            logoFile: null,
            errorMessage: '',

            nextStep() { if (this.step < 3) this.step++; },
            previousStep() { if (this.step > 1) this.step--; },
            handleLogo(e) { this.logoFile = e.target.files[0]; },

            async submit() {
                this.loading = true;
                this.errorMessage = '';

                const formData = new FormData();

                Object.keys(this.form).forEach(key => {
                    if (this.form[key] !== undefined && this.form[key] !== '') {
                        formData.append(key, this.form[key]);
                    }
                });

                if (this.logoFile) formData.append('logo', this.logoFile);

                // Valores da empresa (um por linha)
                if (this.form.valores_text.trim()) {
                    const valores = this.form.valores_text
                        .split('\n')
                        .map(v => v.trim())
                        .filter(v => v.length > 0);
                    valores.forEach(v => formData.append('valores[]', v));
                }

                try {
                    const response = await fetch('/onboarding', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'          // ← ESSA LINHA É A MAIS IMPORTANTE
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        alert('✅ Empresa cadastrada com sucesso!');
                        window.location.href = '/dashboard';
                    } else {
                        // Mostra erros de validação do Laravel
                        let msg = 'Erros encontrados:\n\n';
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                msg += `• ${field}: ${data.errors[field].join(', ')}\n`;
                            });
                        } else {
                            msg += data.message || 'Erro desconhecido';
                        }
                        alert(msg);
                    }
                } catch (err) {
                    console.error(err);
                    alert('❌ Erro ao salvar. Abra o console (F12) e veja o erro completo.');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
    </script>
    @endpush
</x-app-layout>
