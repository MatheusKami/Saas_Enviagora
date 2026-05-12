<x-app-layout>

    <div class="page-header">
        <div>
            <h1>Onboarding da Empresa</h1>
            <p>
                Configure sua empresa e personalize o sistema antes de começar.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- FORM -->
        <div class="xl:col-span-2">

            <form method="POST" action="{{ route('onboarding.store') }}">
                @csrf

                <!-- Empresa -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h2>Informações da Empresa</h2>
                    </div>

                    <div class="card-body">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>
                                <label class="form-label">
                                    Razão Social
                                </label>

                                <input
                                    type="text"
                                    name="razao_social"
                                    class="form-input"
                                    placeholder="Digite a razão social"
                                >
                            </div>

                            <div>
                                <label class="form-label">
                                    CNPJ
                                </label>

                                <input
                                    type="text"
                                    name="cnpj"
                                    class="form-input"
                                    placeholder="00.000.000/0001-00"
                                >
                            </div>

                            <div>
                                <label class="form-label">
                                    Segmento
                                </label>

                                <select
                                    name="segmento"
                                    class="form-input"
                                >
                                    <option>Tecnologia</option>
                                    <option>RH</option>
                                    <option>Marketing</option>
                                    <option>Educação</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">
                                    Funcionários
                                </label>

                                <select
                                    name="funcionarios"
                                    class="form-input"
                                >
                                    <option>1 - 10</option>
                                    <option>11 - 50</option>
                                    <option>51 - 100</option>
                                    <option>100+</option>
                                </select>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- RH -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h2>Equipe de RH</h2>
                    </div>

                    <div class="card-body">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>
                                <label class="form-label">
                                    Responsável
                                </label>

                                <input
                                    type="text"
                                    name="responsavel"
                                    class="form-input"
                                    placeholder="Nome do responsável"
                                >
                            </div>

                            <div>
                                <label class="form-label">
                                    E-mail
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-input"
                                    placeholder="empresa@email.com"
                                >
                            </div>

                        </div>

                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Finalizar Configuração
                    </button>
                </div>

            </form>

        </div>

        <!-- SIDE -->
        <div>

            <div class="card mb-6">
                <div class="card-header">
                    <h2>Progresso</h2>
                </div>

                <div class="card-body">

                    <div class="w-full h-3 rounded-full bg-slate-800 overflow-hidden mb-3">
                        <div class="h-full w-1/3 bg-blue-500 rounded-full"></div>
                    </div>

                    <p class="text-sm text-slate-400">
                        Você está configurando sua empresa.
                    </p>

                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Assistente IA</h2>
                </div>

                <div class="card-body space-y-4">

                    <label class="flex items-center justify-between">
                        <span>Triagem Automática</span>
                        <input type="checkbox" checked>
                    </label>

                    <label class="flex items-center justify-between">
                        <span>Análise Comportamental</span>
                        <input type="checkbox">
                    </label>

                    <label class="flex items-center justify-between">
                        <span>Sugestões IA</span>
                        <input type="checkbox" checked>
                    </label>

                </div>
            </div>

        </div>

    </div>

</x-app-layout>