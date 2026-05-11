<x-app-layout>

    @csrf

    <div class="page-header" style="margin-bottom:0">
        <div>
            <h1>Chat — Assistente de RH</h1>
            <p>Tire dúvidas sobre candidatos, vagas e processos seletivos</p>
        </div>
    </div>

    <div class="chat-wrap" x-data="chatApp()" x-init="init()">

        <!-- Sidebar -->
        <div class="chat-sidebar" :class="{ collapsed: !sidebarOpen }">

            <div class="chat-sidebar-header">
                <i class="ti ti-layout-sidebar-left-expand"></i>
                Contexto
            </div>

            <a href="/chat" class="chat-ctx-item {{ !$job ? 'active' : '' }}">
                <i class="ti ti-building"></i>
                <span class="chat-ctx-label">
                    Geral — empresa
                </span>
            </a>

            @php
                $vagas = \App\Models\Job::where(
                    'company_id',
                    Auth::user()->company_id ?? Auth::user()->empresa_id
                )
                ->whereIn('status', ['ativa', 'em_analise'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            @endphp

            @if($vagas->count())

                <div style="
                    padding:.5rem 1rem .25rem;
                    font-size:.68rem;
                    font-weight:600;
                    letter-spacing:.05em;
                    text-transform:uppercase;
                    color:#64748b
                ">
                    Vagas ativas
                </div>

                @foreach($vagas as $vaga)

                    <a
                        href="/chat?job_id={{ $vaga->id }}"
                        class="chat-ctx-item {{ $job && $job->id === $vaga->id ? 'active' : '' }}"
                    >

                        <i class="ti ti-briefcase"></i>

                        <span class="chat-ctx-label">
                            {{ $vaga->titulo }}
                        </span>

                        @if(\App\Models\ChatMessage::where('job_id', $vaga->id)->count())

                            <span class="chat-ctx-badge">
                                {{ \App\Models\ChatMessage::where('job_id', $vaga->id)->count() }}
                            </span>

                        @endif

                    </a>

                @endforeach

            @endif

        </div>

        <!-- MAIN -->
        <div class="chat-main">

            <!-- HEADER -->
            <div class="chat-header">

                <div class="chat-header-left">

                    <button
                        class="chat-toggle-btn"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <i class="ti ti-layout-sidebar-left-expand"></i>
                    </button>

                    <div class="chat-context-info">

                        <div class="chat-context-title">

                            @if($job)

                                <i class="ti ti-briefcase"></i>
                                {{ $job->titulo }}

                            @else

                                <i class="ti ti-building"></i>

                                Contexto geral —
                                {{ Auth::user()->company->razao_social ?? 'Empresa' }}

                            @endif

                        </div>

                        <div class="chat-context-sub">

                            @if($job)

                                {{ $job->candidates->count() }}
                                candidatos · IA contextualizada

                            @else

                                Selecione uma vaga no painel esquerdo

                            @endif

                        </div>

                    </div>

                </div>

                <div class="chat-header-right">

                    <button
                        class="chat-action-btn"
                        @click="clearHistory()"
                    >
                        <i class="ti ti-trash"></i>
                        Limpar
                    </button>

                </div>

            </div>

            <!-- MESSAGES -->
            <div
                class="chat-messages"
                id="chat-messages"
                x-ref="messages"
            >

                <template x-if="!hasHistory && messages.length === 0">

                    <div class="chat-empty">

                        <i class="ti ti-message-circle"></i>

                        <h3>Como posso ajudar?</h3>

                        <p>
                            Faça perguntas sobre candidatos,
                            vagas ou processos seletivos.
                        </p>

                    </div>

                </template>

                <!-- HISTÓRICO -->
                @foreach($history as $msg)

                    <div class="msg-row {{ $msg->role }}">

                        <div class="msg-avatar {{ $msg->role }}">

                            @if($msg->role === 'assistant')

                                <i class="ti ti-sparkles"></i>

                            @else

                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}

                            @endif

                        </div>

                        <div>

                            <div class="msg-bubble {{ $msg->role }}">
                                {!! nl2br(e($msg->content)) !!}
                            </div>

                            <div class="msg-time">
                                {{ $msg->created_at->format('H:i') }}
                            </div>

                        </div>

                    </div>

                @endforeach

                <!-- TEMPO REAL -->
                <template
                    x-for="(msg, index) in messages"
                    :key="index + '-' + msg.content.length"
                >

                    <div class="msg-row" :class="msg.role">

                        <div class="msg-avatar" :class="msg.role">

                            <template x-if="msg.role === 'assistant'">

                                <i class="ti ti-sparkles"></i>

                            </template>

                            <template x-if="msg.role === 'user'">

                                <span>
                                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                </span>

                            </template>

                        </div>

                        <div>

                            <div class="msg-bubble" :class="msg.role">

                                <div x-html="formatMsg(msg.content)"></div>

                                <span
                                    x-show="msg.streaming"
                                    class="streaming-cursor"
                                ></span>

                            </div>

                            <div
                                class="msg-time"
                                x-text="msg.time"
                            ></div>

                        </div>

                    </div>

                </template>

            </div>

            <!-- INPUT -->
            <div class="chat-input-area">

                <div class="chat-input-wrap">

                    <textarea
                        class="chat-textarea"
                        x-model="input"
                        @keydown.enter.prevent="if(!$event.shiftKey) send()"
                        @input="autoResize($event.target)"
                        :disabled="loading"
                        rows="1"
                        placeholder="Digite sua pergunta..."
                    ></textarea>

                    <button
                        class="chat-send-btn"
                        @click="send()"
                        :disabled="loading || !input.trim()"
                    >

                        <i
                            class="ti"
                            :class="loading ? 'ti-loader-2' : 'ti-send'"
                        ></i>

                    </button>

                </div>

            </div>

        </div>

    </div>

    @push('scripts')

    <script>

    function chatApp() {

        return {

            input: '',

            messages: [],

            loading: false,

            sidebarOpen: true,

            jobId: {{ $job ? $job->id : 'null' }},

            hasHistory: {{ $history->count() > 0 ? 'true' : 'false' }},

            init() {

                this.scrollBottom();

                this.$watch('messages', () => {

                    this.$nextTick(() => {

                        this.scrollBottom();

                    });

                });

            },

            scrollBottom() {

                this.$nextTick(() => {

                    const el = this.$refs.messages;

                    if (el) {

                        el.scrollTop = el.scrollHeight;

                    }

                });

            },

            now() {

                return new Date().toLocaleTimeString(
                    'pt-BR',
                    {
                        hour: '2-digit',
                        minute: '2-digit'
                    }
                );

            },

            autoResize(el) {

                el.style.height = 'auto';

                el.style.height =
                    Math.min(el.scrollHeight, 140) + 'px';

            },

            formatMsg(text) {

                if (!text) return '';

                return text

                    .replace(/&/g, '&amp;')

                    .replace(/</g, '&lt;')

                    .replace(/>/g, '&gt;')

                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')

                    .replace(/\n/g, '<br>');

            },

            async send() {

                const text = this.input.trim();

                if (!text || this.loading) {
                    return;
                }

                this.hasHistory = true;

                // USER MESSAGE
                this.messages.push({

                    id: Date.now(),

                    role: 'user',

                    content: text,

                    time: this.now(),

                    streaming: false

                });

                this.messages = [...this.messages];

                this.input = '';

                this.loading = true;

                this.scrollBottom();

                // AI MESSAGE
                const aiMsg = {

                    id: Date.now() + 1,

                    role: 'assistant',

                    content: '',

                    time: this.now(),

                    streaming: true

                };

                this.messages.push(aiMsg);

                this.messages = [...this.messages];

                this.scrollBottom();

                try {

                    const response = await fetch('/chat/send', {

                        method: 'POST',

                        headers: {

                            'Content-Type': 'application/json',

                            'Accept': 'text/event-stream',

                            'X-CSRF-TOKEN':
                                document.querySelector(
                                    'meta[name="csrf-token"]'
                                ).content,
                        },

                        body: JSON.stringify({

                            message: text,

                            job_id: this.jobId

                        }),
                    });

                    if (!response.ok) {

                        throw new Error(
                            'HTTP ' + response.status
                        );

                    }

                    const reader = response.body.getReader();

                    const decoder = new TextDecoder();

                    let buffer = '';

                    while (true) {

                        const { done, value } =
                            await reader.read();

                        if (done) {
                            break;
                        }

                        buffer += decoder.decode(
                            value,
                            {
                                stream: true
                            }
                        );

                        const chunks = buffer.split('\n\n');

                        buffer = chunks.pop();

                        for (const chunk of chunks) {

                            const lines = chunk.split('\n');

                            for (const line of lines) {

                                if (
                                    !line.startsWith('data: ')
                                ) {
                                    continue;
                                }

                                const payload =
                                    line.replace(
                                        /^data:\s*/,
                                        ''
                                    ).trim();

                                if (
                                    payload === '[DONE]'
                                ) {

                                    aiMsg.streaming = false;

                                    continue;
                                }

                                try {

                                    const data =
                                        JSON.parse(payload);

                                    if (data.error) {

                                        aiMsg.content =
                                            data.error;

                                        aiMsg.streaming =
                                            false;

                                        continue;
                                    }

                                    if (data.chunk) {

                                        aiMsg.content +=
                                            data.chunk;

                                        // força reatividade
                                        this.messages =
                                            [...this.messages];

                                        this.$nextTick(() => {

                                            this.scrollBottom();

                                            requestAnimationFrame(() => {

                                                this.scrollBottom();

                                            });

                                        });

                                    }

                                } catch (e) {

                                    console.error(
                                        'Erro SSE:',
                                        e
                                    );

                                }

                            }

                        }

                    }

                } catch (err) {

                    console.error(err);

                    aiMsg.content =
                        'Erro ao conectar com o assistente.';

                } finally {

                    aiMsg.streaming = false;

                    this.loading = false;

                    this.messages = [...this.messages];

                    this.scrollBottom();

                }

            },

            async clearHistory() {

                if (
                    !confirm(
                        'Limpar histórico da conversa?'
                    )
                ) {
                    return;
                }

                await fetch('/chat/clear', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/json',

                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                    },

                    body: JSON.stringify({

                        job_id: this.jobId

                    }),
                });

                this.messages = [];

                this.hasHistory = false;

                window.location.reload();

            }

        };

    }

    </script>

    @endpush

</x-app-layout>