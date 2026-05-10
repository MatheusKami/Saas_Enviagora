<x-app-layout>

    {{-- Cabeçalho --}}
    <div class="page-header">
        <div>
            <h1>Vagas</h1>
            <p>Gerencie todas as suas vagas abertas</p>
        </div>
        <div style="display:flex;gap:.6rem">
            <a href="{{ route('vagas.create-manual') }}" class="btn">
                <i class="ti ti-plus"></i> Manual
            </a>
            <a href="{{ route('vagas.create-ia') }}" class="btn btn-primary">
                <i class="ti ti-sparkles"></i> Nova vaga com IA
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div style="display:flex;border-bottom:1px solid var(--gray-200)">
            <button onclick="filterStatus('all')" class="tab-btn active" id="tab-all">Todas (4)</button>
            <button onclick="filterStatus('active')" class="tab-btn" id="tab-active">Ativas (3)</button>
            <button onclick="filterStatus('draft')" class="tab-btn" id="tab-draft">Rascunhos (1)</button>
        </div>
        <div style="position:relative;width:280px">
            <i class="ti ti-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px"></i>
            <input type="text" id="search-input" onkeyup="filterVagas()"
                   class="form-input" style="padding-left:2.2rem"
                   placeholder="Buscar por cargo...">
        </div>
    </div>

    {{-- Lista de vagas --}}
    <div id="vagas-list" style="display:flex;flex-direction:column;gap:.75rem">

        <a href="#" class="card vaga-card" data-status="active" style="padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit;transition:box-shadow .2s">
            <div style="width:44px;height:44px;background:#ddeeff;border:1px solid var(--blue-100);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--blue-600);flex-shrink:0">
                <i class="ti ti-briefcase"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.9rem;font-weight:700;margin-bottom:.2rem">Gerente de produto</div>
                <div style="font-size:.75rem;color:var(--gray-400);display:flex;gap:.75rem">
                    <span><i class="ti ti-building" style="font-size:12px"></i> Produto</span>
                    <span><i class="ti ti-users" style="font-size:12px"></i> 7 candidatos</span>
                    <span><i class="ti ti-calendar" style="font-size:12px"></i> 5 dias</span>
                </div>
            </div>
            <span class="badge badge-green"><i class="ti ti-check"></i> Relatório pronto</span>
        </a>

        <a href="#" class="card vaga-card" data-status="active" style="padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit;transition:box-shadow .2s">
            <div style="width:44px;height:44px;background:#f3f0fe;border:1px solid #c5c1f7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#7F77DD;flex-shrink:0">
                <i class="ti ti-code"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.9rem;font-weight:700;margin-bottom:.2rem">Desenvolvedor backend sênior</div>
                <div style="font-size:.75rem;color:var(--gray-400);display:flex;gap:.75rem">
                    <span><i class="ti ti-building" style="font-size:12px"></i> Tecnologia</span>
                    <span><i class="ti ti-users" style="font-size:12px"></i> 4 candidatos</span>
                    <span><i class="ti ti-calendar" style="font-size:12px"></i> 2 dias</span>
                </div>
            </div>
            <span class="badge badge-amber"><i class="ti ti-loader"></i> IA processando</span>
        </a>

        <a href="#" class="card vaga-card" data-status="active" style="padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit;transition:box-shadow .2s">
            <div style="width:44px;height:44px;background:#fdecea;border:1px solid #f5b4b4;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#C0392B;flex-shrink:0">
                <i class="ti ti-palette"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.9rem;font-weight:700;margin-bottom:.2rem">Designer UX/UI</div>
                <div style="font-size:.75rem;color:var(--gray-400);display:flex;gap:.75rem">
                    <span><i class="ti ti-building" style="font-size:12px"></i> Design</span>
                    <span><i class="ti ti-users" style="font-size:12px"></i> 5 candidatos</span>
                    <span><i class="ti ti-calendar" style="font-size:12px"></i> 8 dias</span>
                </div>
            </div>
            <span class="badge badge-blue"><i class="ti ti-clock"></i> Aguardando testes</span>
        </a>

        <a href="#" class="card vaga-card" data-status="draft" style="padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit;transition:box-shadow .2s">
            <div style="width:44px;height:44px;background:var(--gray-100);border:1px solid var(--gray-200);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--gray-400);flex-shrink:0">
                <i class="ti ti-chart-bar"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.9rem;font-weight:700;margin-bottom:.2rem">Analista financeiro</div>
                <div style="font-size:.75rem;color:var(--gray-400);display:flex;gap:.75rem">
                    <span><i class="ti ti-building" style="font-size:12px"></i> Financeiro</span>
                    <span><i class="ti ti-users" style="font-size:12px"></i> 2 candidatos</span>
                    <span><i class="ti ti-calendar" style="font-size:12px"></i> Hoje</span>
                </div>
            </div>
            <span class="badge badge-gray">Rascunho</span>
        </a>

    </div>

    @push('scripts')
    <script>
        // Hover nas vagas
        document.querySelectorAll('.vaga-card').forEach(card => {
            card.addEventListener('mouseenter', () => card.style.boxShadow = '0 4px 12px rgba(0,0,0,.1)');
            card.addEventListener('mouseleave', () => card.style.boxShadow = '');
        });

        function filterStatus(status) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + status).classList.add('active');
            document.querySelectorAll('.vaga-card').forEach(card => {
                card.style.display = (status === 'all' || card.dataset.status === status) ? 'flex' : 'none';
            });
        }

        function filterVagas() {
            const term = document.getElementById('search-input').value.toLowerCase();
            document.querySelectorAll('.vaga-card').forEach(card => {
                card.style.display = card.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
            });
        }
    </script>
    @endpush

</x-app-layout>
