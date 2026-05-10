<x-app-layout>

    {{-- ════════════════════════════════════════════════════════
         CHAT ASSISTENTE DE RH
         Módulo 5 do briefing — sidebar colapsável, streaming SSE
         Histórico salvo no banco por vaga (job_id)
    ════════════════════════════════════════════════════════ --}}

    @push('styles')
    <style>
        /* ── Layout do chat ── */
        .chat-wrap {
            display: flex;
            height: calc(100vh - 60px - 3.5rem); /* 60px topbar + padding do dash-content */
            gap: 0;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow-sm);
        }

        /* ── Painel esquerdo — vagas/contexto ── */
        .chat-sidebar {
            width: 240px;
            flex-shrink: 0;
            border-right: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
            background: var(--gray-50);
            transition: width .25s ease, opacity .25s ease;
            overflow: hidden;
        }
        .chat-sidebar.collapsed {
            width: 0;
            opacity: 0;
            border-right: none;
        }
        .chat-sidebar-header {
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: .8rem;
            font-weight: 700;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .chat-sidebar-header i { color: var(--blue-600); font-size: 15px; }
        .chat-ctx-item {
            padding: .65rem 1rem;
            font-size: .78rem;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-200);
            cursor: pointer;
            transition: background .12s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .chat-ctx-item:hover   { background: var(--blue-50); color: var(--blue-600); }
        .chat-ctx-item.active  { background: var(--blue-50); color: var(--blue-600); font-weight: 600; }
        .chat-ctx-item i { font-size: 14px; flex-shrink: 0; }
        .chat-ctx-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-ctx-badge {
            margin-left: auto;
            font-size: .6rem;
            font-weight: 600;
            padding: 1px 5px;
            border-radius: 10px;
            background: var(--blue-100);
            color: var(--blue-600);
            flex-shrink: 0;
        }

        /* ── Área principal do chat ── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #fff;
        }

        /* Header do chat */
        .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .75rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .chat-toggle-btn {
            width: 30px;
            height: 30px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--gray-600);
            transition: background .12s;
            flex-shrink: 0;
        }
        .chat-toggle-btn:hover { background: var(--gray-100); }
        .chat-context-info { min-width: 0; }
        .chat-context-title {
            font-size: .88rem;
            font-weight: 700;
            color: var(--gray-900);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chat-context-sub {
            font-size: .72rem;
            color: var(--gray-400);
            margin-top: .1rem;
        }
        .chat-header-right { display: flex; align-items: center; gap: .5rem; }
        .chat-action-btn {
            display: flex;
            align-items: center;
            gap: .3rem;
            font-size: .75rem;
            color: var(--gray-500);
            background: none;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            padding: .3rem .7rem;
            cursor: pointer;
            transition: background .12s, color .12s;
        }
        .chat-action-btn:hover { background: var(--gray-100); color: var(--gray-700); }
        .chat-action-btn i { font-size: 13px; }

        /* Mensagens */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scroll-behavior: smooth;
        }

        /* Estado vazio */
        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            color: var(--gray-400);
            gap: .75rem;
        }
        .chat-empty i { font-size: 2.5rem; color: var(--blue-100); }
        .chat-empty h3 { font-size: .95rem; font-weight: 600; color: var(--gray-600); }
        .chat-empty p  { font-size: .8rem; max-width: 280px; line-height: 1.6; }

        /* Sugestões de perguntas */
        .chat-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: center;
            margin-top: .5rem;
        }
        .chat-suggestion {
            font-size: .75rem;
            padding: .4rem .85rem;
            border: 1px solid var(--blue-100);
            border-radius: 20px;
            color: var(--blue-600);
            background: var(--blue-50);
            cursor: pointer;
            transition: background .12s;
        }
        .chat-suggestion:hover { background: var(--blue-100); }

        /* Bubble de mensagem */
        .msg-row {
            display: flex;
            gap: .65rem;
            align-items: flex-start;
        }
        .msg-row.user { flex-direction: row-reverse; }

        .msg-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: .15rem;
        }
        .msg-avatar.ai   { background: var(--blue-600); color: #fff; }
        .msg-avatar.user { background: var(--gray-200); color: var(--gray-700); }

        .msg-bubble {
            max-width: 72%;
            padding: .7rem .95rem;
            border-radius: 12px;
            font-size: .83rem;
            line-height: 1.65;
            word-break: break-word;
        }
        .msg-bubble.ai {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            color: var(--gray-800);
            border-radius: 2px 12px 12px 12px;
        }
        .msg-bubble.user {
            background: var(--blue-600);
            color: #fff;
            border-radius: 12px 2px 12px 12px;
        }
        .msg-bubble p   { margin-bottom: .5rem; }
        .msg-bubble p:last-child { margin-bottom: 0; }
        .msg-bubble strong { font-weight: 600; }
        .msg-bubble ul, .msg-bubble ol {
            padding-left: 1.2rem;
            margin: .4rem 0;
        }
        .msg-bubble li { margin-bottom: .2rem; }

        .msg-time {
            font-size: .65rem;
            color: var(--gray-400);
            margin-top: .3rem;
            text-align: right;
        }
        .msg-row.ai .msg-time { text-align: left; }

        /* Cursor piscante durante streaming */
        .streaming-cursor::after {
            content: '▋';
            animation: blink .7s step-end infinite;
            color: var(--blue-600);
            margin-left: 1px;
        }
        @keyframes blink { 50% { opacity: 0; } }

        /* Indicador de digitando */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: .6rem .95rem;
        }
        .typing-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--gray-400);
            animation: typing-bounce .9s infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: .15s; }
        .typing-dot:nth-child(3) { animation-delay: .3s; }
        @keyframes typing-bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30%           { transform: translateY(-5px); }
        }

        /* Input area */
        .chat-input-area {
            padding: .9rem 1.25rem;
            border-top: 1px solid var(--gray-200);
            flex-shrink: 0;
            background: #fff;
        }
        .chat-input-wrap {
            display: flex;
            align-items: flex-end;
            gap: .6rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: .55rem .75rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .chat-input-wrap:focus-within {
            border-color: var(--blue-600);
            box-shadow: 0 0 0 3px rgba(24,95,165,.1);
            background: #fff;
        }
        .chat-textarea {
            flex: 1;
            border: none;
            background: transparent;
            font-size: .85rem;
            font-family: inherit;
            color: var(--gray-900);
            resize: none;
            outline: none;
            line-height: 1.5;
            max-height: 120px;
            min-height: 22px;
        }
        .chat-textarea::placeholder { color: var(--gray-400); }
        .chat-send-btn {
            width: 34px;
            height: 34px;
            background: var(--blue-600);
            border: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #fff;
            font-size: 16px;
            flex-shrink: 0;
            transition: opacity .15s, transform .1s;
        }
        .chat-send-btn:hover   { opacity: .88; }
        .chat-send-btn:active  { transform: scale(.95); }
        .chat-send-btn:disabled { opacity: .4; cursor: not-allowed; }

        .chat-input-hint {
            font-size: .7rem;
            color: var(--gray-400);
            margin-top: .4rem;
            text-align: center;
        }

        /* Disclaimer SATEPSI */
        .chat-disclaimer {
            font-size: .68rem;
            color: var(--gray-400);
            text-align: center;
            padding: .4rem 1.25rem 0;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .chat-sidebar { display: none; }
            .chat-wrap    { height: calc(100vh - 60px - 2rem); }
        }
    </style>
    @endpush

    {{-- ── Cabeçalho da página ── --}}
    <div class="page-header" style="margin-bottom:0">
        <div>
            <h1>Chat — Assistente de RH</h1>
            <p>Tire dúvidas sobre candidatos, vagas e processos seletivos</p>
        </div>
    </div>

    {{-- ── Interface do chat ── --}}
    <div class="chat-wrap" x-data="chatApp()" x-init="init()">

        {{-- Sidebar de contexto --}}
        <div class="chat-sidebar" :class="{ collapsed: !sidebarOpen }">
            <div class="chat-sidebar-header">
                <i class="ti ti-layout-sidebar-left-expand"></i>
                Contexto
            </div>

            {{-- Contexto geral --}}
            <a href="/chat" class="chat-ctx-item {{ !$job ? 'active' : '' }}">
                <i class="ti ti-building"></i>
                <span class="chat-ctx-label">Geral — empresa</span>
            </a>

            {{-- Vagas ativas --}}
            @php
                $vagas = \App\Models\Job::where('company_id', Auth::user()->company_id ?? Auth::user()->empresa_id)
                            ->whereIn('status', ['ativa', 'em_analise'])
                            ->orderBy('created_at', 'desc')
                            ->take(10)
                            ->get();
            @endphp

            @if($vagas->count())
                <div style="padding:.5rem 1rem .25rem;font-size:.68rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--gray-400)">
                    Vagas ativas
                </div>
                @foreach($vagas as $vaga)
                    <a href="/chat?job_id={{ $vaga->id }}"
                       class="chat-ctx-item {{ $job && $job->id === $vaga->id ? 'active' : '' }}">
                        <i class="ti ti-briefcase"></i>
                        <span class="chat-ctx-label">{{ $vaga->titulo }}</span>
                        @php
                            $msgCount = \App\Models\ChatMessage::where('job_id', $vaga->id)->count();
                        @endphp
                        @if($msgCount)
                            <span class="chat-ctx-badge">{{ $msgCount }}</span>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>

        {{-- Área principal --}}
        <div class="chat-main">

            {{-- Header --}}
            <div class="chat-header">
                <div class="chat-header-left">
                    <button class="chat-toggle-btn" @click="sidebarOpen = !sidebarOpen" title="Alternar contexto">
                        <i class="ti ti-layout-sidebar-left-expand" style="font-size:16px"></i>
                    </button>
                    <div class="chat-context-info">
                        <div class="chat-context-title">
                            @if($job)
                                <i class="ti ti-briefcase" style="color:var(--blue-600);font-size:14px"></i>
                                {{ $job->titulo }}
                            @else
                                <i class="ti ti-building" style="color:var(--blue-600);font-size:14px"></i>
                                Contexto geral — {{ Auth::user()->company->razao_social ?? 'Empresa' }}
                            @endif
                        </div>
                        <div class="chat-context-sub">
                            @if($job)
                                {{ $job->candidates->count() }} candidatos · IA com contexto completo
                            @else
                                Selecione uma vaga no painel esquerdo para contexto específico
                            @endif
                        </div>
                    </div>
                </div>
                <div class="chat-header-right">
                    <button class="chat-action-btn" @click="clearHistory()" title="Limpar conversa">
                        <i class="ti ti-trash"></i> Limpar
                    </button>
                </div>
            </div>

            {{-- Mensagens --}}
            <div class="chat-messages" id="chat-messages" x-ref="messages">

                {{-- Estado vazio --}}
                <template x-if="messages.length === 0">
                    <div class="chat-empty">
                        <i class="ti ti-message-circle"></i>
                        <h3>Como posso ajudar?</h3>
                        <p>Faça perguntas sobre candidatos, vagas, perfis psicométricos ou processos seletivos.</p>
                        <div class="chat-suggestions">
                            <button class="chat-suggestion" @click="sendSuggestion('Qual candidato você recomendaria para fechar amanhã?')">
                                Quem fechar amanhã?
                            </button>
                            <button class="chat-suggestion" @click="sendSuggestion('Como o líder deve dar feedback para o candidato mais bem ranqueado?')">
                                Como dar feedback?
                            </button>
                            <button class="chat-suggestion" @click="sendSuggestion('Quais perguntas devo fazer na próxima entrevista?')">
                                Perguntas para entrevista
                            </button>
                            <button class="chat-suggestion" @click="sendSuggestion('O budget salarial dessa vaga está adequado para o mercado?')">
                                Budget adequado?
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Histórico salvo no banco --}}
                @foreach($history as $msg)
                    <div class="msg-row {{ $msg->role }}">
                        <div class="msg-avatar {{ $msg->role }}">
                            @if($msg->role === 'assistant')
                                <i class="ti ti-sparkles" style="font-size:13px"></i>
                            @else
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            @endif
                        </div>
                        <div>
                            <div class="msg-bubble {{ $msg->role }}">{!! nl2br(e($msg->content)) !!}</div>
                            <div class="msg-time">{{ $msg->created_at->format('H:i') }}</div>
                        </div>
                    </div>
                @endforeach

                {{-- Mensagens da sessão atual (Alpine) --}}
                <template x-for="msg in messages" :key="msg.id">
                    <div class="msg-row" :class="msg.role">
                        <div class="msg-avatar" :class="msg.role">
                            <template x-if="msg.role === 'assistant'">
                                <i class="ti ti-sparkles" style="font-size:13px"></i>
                            </template>
                            <template x-if="msg.role === 'user'">
                                <span>{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                            </template>
                        </div>
                        <div>
                            <div class="msg-bubble" :class="msg.role">
                                <span x-html="formatMsg(msg.content)"></span>
                                <span x-show="msg.streaming" class="streaming-cursor"></span>
                            </div>
                            <div class="msg-time" x-text="msg.time"></div>
                        </div>
                    </div>
                </template>

            </div>

            {{-- Input --}}
            <div class="chat-input-area">
                <div class="chat-input-wrap">
                    <textarea
                        class="chat-textarea"
                        x-model="input"
                        @keydown.enter.prevent="if(!$event.shiftKey) send()"
                        @input="autoResize($event.target)"
                        :disabled="loading"
                        placeholder="Digite sua pergunta... (Enter para enviar, Shift+Enter para nova linha)"
                        rows="1"
                        id="chat-input"
                    ></textarea>
                    <button
                        class="chat-send-btn"
                        @click="send()"
                        :disabled="loading || !input.trim()"
                        title="Enviar"
                    >
                        <i class="ti" :class="loading ? 'ti-loader-2' : 'ti-send'" style="font-size:15px"></i>
                    </button>
                </div>
                <div class="chat-disclaimer">
                    ⚠️ Este assistente é uma ferramenta de apoio ao RH, não substitui avaliação psicológica clínica (SATEPSI/CFP).
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
    function chatApp() {
        return {
            input:       '',
            messages:    [],
            loading:     false,
            sidebarOpen: true,
            jobId:       {{ $job ? $job->id : 'null' }},

            init() {
                // Se já há histórico do banco, não mostra o estado vazio
                const hasHistory = {{ $history->count() > 0 ? 'true' : 'false' }};
                if (hasHistory) this.scrollBottom();
            },

            scrollBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            now() {
                return new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            },

            // Auto-resize do textarea conforme o usuário digita
            autoResize(el) {
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 120) + 'px';
            },

            // Formata o texto para exibição (quebras de linha, negrito básico)
            formatMsg(text) {
                if (!text) return '';
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
            },

            sendSuggestion(text) {
                this.input = text;
                this.send();
            },

            async send() {
                const text = this.input.trim();
                if (!text || this.loading) return;

                // Adiciona mensagem do usuário
                const userMsg = { id: Date.now(), role: 'user', content: text, time: this.now(), streaming: false };
                this.messages.push(userMsg);
                this.input = '';

                // Reset textarea height
                const ta = document.getElementById('chat-input');
                if (ta) { ta.style.height = 'auto'; }

                this.loading = true;
                this.scrollBottom();

                // Adiciona bubble da IA (vazia, vai preenchendo via stream)
                const aiMsg = { id: Date.now() + 1, role: 'assistant', content: '', time: this.now(), streaming: true };
                this.messages.push(aiMsg);
                this.scrollBottom();

                try {
                    const res = await fetch('/chat/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'text/event-stream',
                        },
                        body: JSON.stringify({
                            message: text,
                            job_id:  this.jobId,
                        }),
                    });

                    if (!res.ok) throw new Error('Erro na requisição');

                    const reader  = res.body.getReader();
                    const decoder = new TextDecoder();
                    let   buffer  = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop(); // guarda linha incompleta

                        for (const line of lines) {
                            if (!line.startsWith('data: ')) continue;
                            const payload = line.slice(6).trim();
                            if (payload === '[DONE]') break;

                            try {
                                const data = JSON.parse(payload);
                                if (data.error) {
                                    aiMsg.content  = data.error;
                                    aiMsg.streaming = false;
                                } else if (data.chunk) {
                                    aiMsg.content += data.chunk;
                                    this.scrollBottom();
                                }
                            } catch (_) { /* ignora JSON inválido */ }
                        }
                    }

                } catch (err) {
                    aiMsg.content = 'Erro ao conectar com o assistente. Verifique sua conexão e tente novamente.';
                } finally {
                    aiMsg.streaming = false;
                    this.loading    = false;
                    this.scrollBottom();
                }
            },

            async clearHistory() {
                if (!confirm('Limpar todo o histórico desta conversa?')) return;

                await fetch('/chat/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ job_id: this.jobId }),
                });

                this.messages = [];
                // Recarrega para limpar histórico do banco que veio via PHP
                window.location.reload();
            },
        };
    }
    </script>
    @endpush

</x-app-layout>
