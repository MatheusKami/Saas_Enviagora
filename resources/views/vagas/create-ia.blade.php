<x-app-layout>

    {{-- Cabeçalho --}}
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:.75rem">
            <a href="{{ route('vagas.index') }}" class="btn" style="padding:.45rem .7rem">
                <i class="ti ti-arrow-left" style="font-size:16px"></i>
            </a>
            <div>
                <h1>Nova vaga com IA</h1>
                <p>Descreva o cargo e deixe a IA gerar tudo</p>
            </div>
        </div>
    </div>

    {{-- Grid principal --}}
    <div style="display:grid;grid-template-columns:1fr 420px;gap:1.5rem;align-items:start">

        {{-- Col esquerda — Briefing --}}
        <div class="card" style="padding:1.75rem">
            <h2 style="font-size:1rem;font-weight:700;display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem">
                <i class="ti ti-sparkles" style="color:#BA7517"></i> Briefing da vaga
            </h2>

            <div style="display:flex;flex-direction:column;gap:1.25rem">

                <div>
                    <label class="form-label">Cargo da vaga <span style="color:var(--red)">*</span></label>
                    <input type="text" id="cargo" class="form-input" placeholder="Ex: Gerente de produto sênior">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div>
                        <label class="form-label">Líder direto</label>
                        <select id="lider" class="form-input form-select">
                            <option value="">Selecione do organograma...</option>
                            <option value="1">Maria Silva — Head de Produto</option>
                            <option value="2">João Mendes — CTO</option>
                        </select>
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
                </div>

                <div>
                    <label class="form-label">Por que está abrindo esta vaga?</label>
                    <select id="motivo" class="form-input form-select">
                        <option value="">Selecione...</option>
                        <option value="crescimento">Crescimento da equipe</option>
                        <option value="substituicao">Substituição</option>
                        <option value="nova_area">Nova área</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Principais responsabilidades <span style="color:var(--red)">*</span></label>
                    <textarea id="responsabilidades" class="form-input form-textarea" rows="5"
                              placeholder="Descreva o dia a dia da posição, os principais desafios e entregas esperadas..."></textarea>
                </div>

                <div>
                    <label class="form-label">Metas e OKRs <span style="font-size:.72rem;font-weight:400;color:var(--gray-400)">(opcional)</span></label>
                    <textarea id="metas" class="form-input form-textarea" rows="3"
                              placeholder="Resultados esperados nos primeiros 30/60/90 dias..."></textarea>
                </div>

                <button type="button" onclick="gerarVagaComIA()"
                        class="btn btn-primary" style="width:100%;justify-content:center;padding:.75rem;font-size:.9rem">
                    <i class="ti ti-sparkles"></i> Gerar vaga com IA
                </button>

            </div>
        </div>

        {{-- Col direita — Resultado IA --}}
        <div class="card" style="padding:1.75rem;min-height:480px;display:flex;flex-direction:column">
            <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem">Resultado gerado pela IA</h2>

            {{-- Placeholder --}}
            <div id="ia-placeholder" style="flex:1;display:flex;align-items:center;justify-content:center;text-align:center;flex-direction:column;gap:.75rem">
                <i class="ti ti-sparkles" style="font-size:3rem;color:var(--blue-100)"></i>
                <p style="font-size:.85rem;color:var(--gray-400);max-width:200px">Preencha o briefing ao lado e clique em Gerar</p>
            </div>

            {{-- Loading --}}
            <div id="ia-loading" style="display:none;flex:1;align-items:center;justify-content:center;flex-direction:column;gap:1rem">
                <div style="width:40px;height:40px;border:3px solid var(--blue-100);border-top-color:var(--blue-600);border-radius:50%;animation:spin 1s linear infinite"></div>
                <p style="font-size:.82rem;color:var(--gray-400)">IA processando o briefing...</p>
            </div>

            {{-- Resultado --}}
            <div id="ia-result" style="display:none;flex:1;flex-direction:column;gap:1rem">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                    <h3 id="ia-cargo-title" style="font-size:1.05rem;font-weight:700"></h3>
                    <span class="badge badge-green">Gerado por IA</span>
                </div>

                <div style="font-size:.82rem;color:var(--gray-600);line-height:1.7;flex:1;overflow-y:auto" id="ia-jd"></div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:.9rem;background:var(--gray-50);border-radius:8px;border:1px solid var(--gray-200)">
                    <div>
                        <div style="font-size:.72rem;color:var(--gray-400);font-weight:600;margin-bottom:.2rem">FAIXA SALARIAL</div>
                        <div id="ia-salario" style="font-size:1rem;font-weight:800;color:var(--green)"></div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:var(--gray-400);font-weight:600;margin-bottom:.2rem">PERFIL IDEAL</div>
                        <div id="ia-perfil" style="font-size:.82rem;font-weight:600"></div>
                    </div>
                </div>

                <div style="display:flex;gap:.6rem;padding-top:.5rem;border-top:1px solid var(--gray-200)">
                    <button onclick="salvarVaga()" class="btn" style="flex:1;justify-content:center">
                        <i class="ti ti-device-floppy"></i> Salvar rascunho
                    </button>
                    <button onclick="publicarVaga()" class="btn btn-primary" style="flex:1;justify-content:center">
                        <i class="ti ti-check"></i> Publicar vaga
                    </button>
                </div>
            </div>
        </div>

    </div>

    @push('styles')
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 900px) {
            .dash-content > div[style*="grid-template-columns:1fr 420px"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        function gerarVagaComIA() {
            const cargo = document.getElementById('cargo').value.trim();
            if (!cargo) {
                document.getElementById('cargo').focus();
                document.getElementById('cargo').style.borderColor = 'var(--red)';
                return;
            }
            document.getElementById('cargo').style.borderColor = '';
            document.getElementById('ia-placeholder').style.display = 'none';
            document.getElementById('ia-result').style.display = 'none';
            document.getElementById('ia-loading').style.display = 'flex';

            // Simula chamada à API — substituir por fetch real ao controller
            setTimeout(() => {
                document.getElementById('ia-loading').style.display = 'none';
                document.getElementById('ia-result').style.display = 'flex';
                document.getElementById('ia-cargo-title').textContent = cargo;
                document.getElementById('ia-jd').innerHTML = `
                    <p><strong>Sobre a vaga:</strong> Buscamos um profissional para liderar iniciativas estratégicas de produto, trabalhando em conjunto com as equipes de design, engenharia e negócios.</p>
                    <br>
                    <p><strong>Responsabilidades:</strong></p>
                    <ul style="padding-left:1.2rem;margin-top:.4rem">
                        <li>Definir roadmap e priorização de features</li>
                        <li>Conduzir discovery com usuários</li>
                        <li>Alinhar OKRs com stakeholders</li>
                        <li>Liderar cerimônias ágeis</li>
                    </ul>
                `;
                document.getElementById('ia-salario').textContent = 'R$ 8.000 – R$ 12.000';
                document.getElementById('ia-perfil').textContent = 'DISC: D/I · INTJ · Eneag: 3';
            }, 2200);
        }

        function salvarVaga()  { alert('Vaga salva como rascunho!'); }
        function publicarVaga() { alert('Vaga publicada com sucesso!'); }
    </script>
    @endpush

</x-app-layout>
