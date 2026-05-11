{{--
    Substitua o bloco Alpine.js do seu chat.blade.php por este.
    Cole dentro do seu layout existente onde está o componente de chat.
--}}

<div
    x-data="chatApp()"
    x-init="init()"
    class="flex flex-col h-full"
>
    {{-- Área de mensagens --}}
    <div
        id="messages"
        class="flex-1 overflow-y-auto p-4 space-y-3"
        x-ref="messages"
    >
        <template x-for="(msg, index) in history" :key="index">
            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                <div
                    :class="msg.role === 'user'
                        ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm px-4 py-2 max-w-xs lg:max-w-md'
                        : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm px-4 py-2 max-w-xs lg:max-w-md'"
                    x-text="msg.content"
                ></div>
            </div>
        </template>

        {{-- Indicador de "digitando..." --}}
        <div x-show="loading" class="flex justify-start">
            <div class="bg-gray-100 text-gray-500 rounded-2xl rounded-bl-sm px-4 py-2">
                <span class="animate-pulse">Digitando...</span>
            </div>
        </div>
    </div>

    {{-- Formulário de envio --}}
    <div class="border-t p-4">
        <div class="flex gap-2">
            <textarea
                x-model="message"
                @keydown.enter.prevent="if(!$event.shiftKey) send()"
                :disabled="loading"
                placeholder="Digite sua mensagem... (Enter para enviar, Shift+Enter para nova linha)"
                rows="1"
                class="flex-1 resize-none border rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            ></textarea>

            <button
                @click="send()"
                :disabled="loading || message.trim() === ''"
                class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Enviar
            </button>

            <button
                @click="clearChat()"
                title="Limpar conversa"
                class="border rounded-xl px-3 py-2 text-gray-500 hover:bg-gray-100 transition"
            >
                🗑
            </button>
        </div>

        <p x-show="errorMsg" x-text="errorMsg" class="text-red-500 text-sm mt-1"></p>
    </div>
</div>

@push('scripts')
<script>
function chatApp() {
    return {
        message: '',
        history: [],   // [{ role: 'user'|'assistant', content: '...' }]
        loading: false,
        errorMsg: '',

        init() {
            // Mensagem de boas-vindas
            this.history.push({
                role: 'assistant',
                content: 'Olá! Sou o assistente de RH. Como posso te ajudar hoje?'
            });
        },

        async send() {
            const text = this.message.trim();
            if (!text || this.loading) return;

            this.errorMsg = '';
            this.message  = '';
            this.loading  = true;

            // Adiciona a mensagem do usuário no histórico local
            this.history.push({ role: 'user', content: text });
            this.$nextTick(() => this.scrollBottom());

            try {
                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: text,
                        // Envia apenas as últimas 10 trocas para não estourar o contexto
                        history: this.history.slice(-20).filter(m => m.role !== 'system'),
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    this.errorMsg = data.error ?? 'Erro ao processar sua mensagem.';
                    return;
                }

                this.history.push({ role: 'assistant', content: data.response });
                this.$nextTick(() => this.scrollBottom());

            } catch (e) {
                this.errorMsg = 'Erro de conexão. Verifique sua internet e tente novamente.';
                console.error('Chat error:', e);
            } finally {
                this.loading = false;
            }
        },

        async clearChat() {
            this.history  = [];
            this.errorMsg = '';
            this.init();

            // Notifica o backend (opcional)
            await fetch('{{ route("chat.clear") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).catch(() => {});
        },

        scrollBottom() {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        },
    };
}
</script>
@endpush
