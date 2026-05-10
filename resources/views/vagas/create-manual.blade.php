<x-app-layout>

    {{-- Cabeçalho --}}
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:.75rem">
            <a href="{{ route('vagas.index') }}" class="btn" style="padding:.45rem .7rem">
                <i class="ti ti-arrow-left" style="font-size:16px"></i>
            </a>
            <div>
                <h1>Criar vaga manualmente</h1>
                <p>Preencha os dados da vaga e adicione os candidatos</p>
            </div>
        </div>
    </div>

    <form id="form-vaga-manual" style="display:flex;flex-direction:column;gap:1.5rem">

        {{-- Dados da vaga --}}
        <div class="card" style="padding:1.75rem">
            <h2 style="font-size:1rem;font-weight:700;display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem">
                <i class="ti ti-briefcase" style="color:var(--blue-600)"></i> Dados da vaga
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">

                <div style="grid-column:1/-1">
                    <label class="form-label">Cargo da vaga <span style="color:var(--red)">*</span></label>
                    <input type="text" id="cargo" required class="form-input" placeholder="Ex: Gerente de produto sênior">
                </div>

                <div>
                    <label class="form-label">Departamento</label>
                    <input type="text" id="departamento" class="form-input" placeholder="Ex: Tecnologia">
                </div>

                <div>
                    <label class="form-label">Senioridade</label>
                    <select id="senioridade" class="form-input form-select">
                        <option value="junior">Júnior</option>
                        <option value="pleno" selected>Pleno</option>
                        <option value="senior">Sênior</option>
                        <option value="lider">Liderança</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Líder direto</label>
                    <select id="lider" class="form-input form-select">
                        <option value="">Selecione do organograma...</option>
                        <option value="1">Maria Silva — Head de Produto</option>
                        <option value="2">João Mendes — CTO</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Por que está abrindo?</label>
                    <select id="motivo" class="form-input form-select">
                        <option value="">Selecione...</option>
                        <option>Crescimento da equipe</option>
                        <option>Substituição</option>
                        <option>Nova área</option>
                    </select>
                </div>

                <div style="grid-column:1/-1">
                    <label class="form-label">Principais responsabilidades <span style="color:var(--red)">*</span></label>
                    <textarea id="responsabilidades" class="form-input form-textarea" rows="4"
                              placeholder="Descreva o dia a dia e as principais entregas..."></textarea>
                </div>

                <div style="grid-column:1/-1">
                    <label class="form-label">Metas / OKRs <span style="font-size:.72rem;font-weight:400;color:var(--gray-400)">(opcional)</span></label>
                    <textarea id="metas" class="form-input form-textarea" rows="3"
                              placeholder="Resultados esperados nos primeiros 30/60/90 dias..."></textarea>
                </div>

            </div>
        </div>

        {{-- Candidatos --}}
        <div class="card" style="padding:1.75rem">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
                <h2 style="font-size:1rem;font-weight:700;display:flex;align-items:center;gap:.5rem">
                    <i class="ti ti-users" style="color:var(--blue-600)"></i> Candidatos
                </h2>
                <button type="button" onclick="adicionarCandidato()" class="btn btn-primary">
                    <i class="ti ti-plus"></i> Adicionar candidato
                </button>
            </div>

            <div id="lista-candidatos" style="display:flex;flex-direction:column;gap:1rem"></div>

            <div id="empty-candidatos" style="text-align:center;padding:2rem;color:var(--gray-400);font-size:.85rem;border:1px dashed var(--gray-200);border-radius:8px;display:none">
                <i class="ti ti-users" style="font-size:2rem;display:block;margin-bottom:.5rem;color:var(--gray-300)"></i>
                Nenhum candidato adicionado ainda
            </div>
        </div>

        {{-- Ações --}}
        <div style="display:flex;justify-content:flex-end;gap:.75rem;padding-bottom:1rem">
            <a href="{{ route('vagas.index') }}" class="btn">Cancelar</a>
            <button type="button" onclick="salvarVagaManual()" class="btn btn-primary" style="padding:.6rem 1.5rem">
                <i class="ti ti-device-floppy"></i> Salvar vaga
            </button>
        </div>

    </form>

    @push('scripts')
    <script>
        let count = 0;

        function candidatoHTML(i) {
            return `
            <div class="candidato-item" data-index="${i}"
                 style="border:1px solid var(--gray-200);border-radius:var(--radius);padding:1.25rem;position:relative">

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                    <span style="font-size:.85rem;font-weight:700;color:var(--gray-700)">Candidato #${i}</span>
                    <button type="button" onclick="removerCandidato(this)"
                            style="background:none;border:none;color:var(--gray-400);cursor:pointer;padding:.2rem;line-height:1"
                            title="Remover">
                        <i class="ti ti-x" style="font-size:16px"></i>
                    </button>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
                    <div>
                        <label class="form-label">Nome completo</label>
                        <input type="text" class="form-input" placeholder="Nome do candidato">
                    </div>
                    <div>
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-input" placeholder="email@exemplo.com">
                    </div>
                    <div>
                        <label class="form-label">LinkedIn <span style="font-size:.7rem;color:var(--gray-400)">(opcional)</span></label>
                        <input type="url" class="form-input" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div>
                        <label class="form-label">Currículo PDF <span style="font-size:.7rem;color:var(--gray-400)">(opcional)</span></label>
                        <input type="file" accept=".pdf" class="form-input" style="padding:.4rem .75rem;cursor:pointer">
                    </div>
                    <div style="grid-column:1/-1">
                        <label class="form-label">Testes de personalidade <span style="font-size:.7rem;color:var(--gray-400)">(DISC / Eneagrama / 16P — opcional)</span></label>
                        <input type="text" class="form-input" placeholder="Cole os resultados ou deixe em branco para enviar link depois">
                    </div>
                    <div style="grid-column:1/-1">
                        <label class="form-label">Transcrição de entrevista <span style="font-size:.7rem;color:var(--gray-400)">(opcional)</span></label>
                        <textarea class="form-input form-textarea" rows="3" placeholder="Cole a transcrição ou anotações da entrevista..."></textarea>
                    </div>
                </div>
            </div>`;
        }

        function atualizarEmpty() {
            const lista = document.getElementById('lista-candidatos');
            const empty = document.getElementById('empty-candidatos');
            empty.style.display = lista.children.length === 0 ? 'block' : 'none';
        }

        function adicionarCandidato() {
            count++;
            document.getElementById('lista-candidatos').insertAdjacentHTML('beforeend', candidatoHTML(count));
            atualizarEmpty();
        }

        function removerCandidato(btn) {
            if (confirm('Remover este candidato?')) {
                btn.closest('.candidato-item').remove();
                atualizarEmpty();
            }
        }

        function salvarVagaManual() {
            const cargo = document.getElementById('cargo').value.trim();
            if (!cargo) {
                document.getElementById('cargo').focus();
                document.getElementById('cargo').style.borderColor = 'var(--red)';
                return;
            }
            document.getElementById('cargo').style.borderColor = '';
            alert(`Vaga "${cargo}" salva com sucesso!`);
        }

        // Inicia com 1 candidato
        window.addEventListener('DOMContentLoaded', adicionarCandidato);
    </script>
    @endpush

</x-app-layout>
