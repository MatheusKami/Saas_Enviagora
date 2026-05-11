@php
    use Illuminate\Support\Facades\Storage;

    $logoUrl = null;

    if (!empty($company->logo_url)) {

        // Se já vier com /storage/
        if (str_contains($company->logo_url, '/storage/')) {
            $logoUrl = $company->logo_url;
        } else {
            $logoUrl = Storage::url($company->logo_url);
        }
    }
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Dados da Empresa') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Atualize as informações cadastrais da sua empresa.') }}
        </p>
    </header>

    {{-- Success --}}
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition
        x-init="setTimeout(() => show = false, 3000)"
        class="mt-4 text-sm text-green-600"
    >
        @if (session('success'))
            {{ session('success') }}
        @endif
    </div>

    <form
        id="form-empresa-dados"
        x-data="empresaDadosForm()"
        @submit.prevent="submit"
        enctype="multipart/form-data"
        class="mt-6 space-y-6"
    >

        {{-- Razão Social --}}
        <div>
            <x-input-label
                for="razao_social"
                :value="__('Razão Social')"
            />

            <x-text-input
                id="razao_social"
                name="razao_social"
                type="text"
                class="mt-1 block w-full"
                x-model="form.razao_social"
                required
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('razao_social')"
            />
        </div>

        {{-- CNPJ --}}
        <div>
            <x-input-label
                for="cnpj"
                :value="__('CNPJ')"
            />

            <x-text-input
                id="cnpj"
                name="cnpj"
                type="text"
                class="mt-1 block w-full"
                x-model="form.cnpj"
                placeholder="00.000.000/0000-00"
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('cnpj')"
            />
        </div>

        {{-- Endereço --}}
        <div>
            <x-input-label
                for="endereco_completo"
                :value="__('Endereço completo')"
            />

            <textarea
                id="endereco_completo"
                name="endereco_completo"
                rows="2"
                x-model="form.endereco_completo"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                placeholder="Rua, número, cidade, estado"
            ></textarea>

            <x-input-error
                class="mt-2"
                :messages="$errors->get('endereco_completo')"
            />
        </div>

        {{-- Site --}}
        <div>
            <x-input-label
                for="url_empresa"
                :value="__('Site da empresa')"
            />

            <x-text-input
                id="url_empresa"
                name="url_empresa"
                type="url"
                class="mt-1 block w-full"
                x-model="form.url_empresa"
                placeholder="https://"
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('url_empresa')"
            />
        </div>

        {{-- Logo --}}
        <div>
            <x-input-label
                for="logo"
                :value="__('Logo da empresa')"
            />

            {{-- Preview --}}
            <div
                x-show="logoPreview || form.logo_url_atual"
                class="mt-3 mb-4"
            >
                <img
                    :src="logoPreview || form.logo_url_atual"
                    alt="Logo da empresa"
                    class="h-20 w-20 object-contain rounded-xl border border-gray-200 bg-white p-2 shadow-sm"
                >

                <p class="text-xs text-gray-400 mt-2">
                    Logo atual
                </p>
            </div>

            <input
                id="logo"
                name="logo"
                type="file"
                accept="image/*"
                @change="handleLogo($event)"
                class="mt-1 block w-full text-sm text-gray-500
                       file:mr-4
                       file:py-2
                       file:px-4
                       file:rounded-md
                       file:border-0
                       file:text-sm
                       file:font-medium
                       file:bg-indigo-50
                       file:text-indigo-700
                       hover:file:bg-indigo-100"
            >

            <p class="mt-1 text-xs text-gray-400">
                JPG, PNG, SVG ou WEBP • Máx. 5MB
            </p>

            <x-input-error
                class="mt-2"
                :messages="$errors->get('logo')"
            />
        </div>

        {{-- Error --}}
        <div
            x-show="errorMessage"
            x-text="errorMessage"
            class="text-sm text-red-600 whitespace-pre-line"
        ></div>

        {{-- Submit --}}
        <div class="flex items-center gap-4">

            <x-primary-button
                x-bind:disabled="loading"
            >
                <span x-show="!loading">
                    {{ __('Salvar') }}
                </span>

                <span x-show="loading">
                    {{ __('Salvando...') }}
                </span>
            </x-primary-button>

        </div>

    </form>
</section>

@push('scripts')
<script>
function empresaDadosForm() {

    return {

        loading: false,

        errorMessage: '',

        logoFile: null,

        logoPreview: null,

        form: {

            razao_social: @json(old('razao_social', $company->razao_social ?? '')),

            cnpj: @json(old('cnpj', $company->cnpj ?? '')),

            endereco_completo: @json(old('endereco_completo', $company->endereco_completo ?? '')),

            url_empresa: @json(old('url_empresa', $company->url_empresa ?? '')),

            logo_url_atual: @json($logoUrl),
        },

        handleLogo(event) {

            const file = event.target.files[0];

            if (!file) return;

            this.logoFile = file;

            this.logoPreview = URL.createObjectURL(file);
        },

        async submit() {

            this.loading = true;

            this.errorMessage = '';

            const formData = new FormData();

            formData.append('_method', 'PUT');

            formData.append(
                'razao_social',
                this.form.razao_social ?? ''
            );

            formData.append(
                'cnpj',
                this.form.cnpj ?? ''
            );

            formData.append(
                'endereco_completo',
                this.form.endereco_completo ?? ''
            );

            formData.append(
                'url_empresa',
                this.form.url_empresa ?? ''
            );

            if (this.logoFile) {

                formData.append(
                    'logo',
                    this.logoFile
                );
            }

            try {

                const response = await fetch('/editar_empresa', {

                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,

                        'Accept': 'application/json',
                    },

                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {

                    const mensagens = data.errors
                        ? Object.values(data.errors)
                            .flat()
                            .join('\n')
                        : (data.message || 'Erro desconhecido.');

                    throw new Error(mensagens);
                }

                window.location.href = data.redirect;

            } catch (error) {

                console.error(error);

                this.errorMessage = error.message;

            } finally {

                this.loading = false;
            }
        }
    };
}
</script>
@endpush