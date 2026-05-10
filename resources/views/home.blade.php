<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/inicio.css">
    <title>Home</title>
</head>
<body>
    <div class="page">

        <div class="left-panel">
            <div class="left-bg-circle" style="width:400px;height:400px;top:-150px;right:-150px"></div>
            <div class="left-bg-circle" style="width:250px;height:250px;bottom:-80px;left:-60px"></div>

            <div class="left-logo">
                <div class="logo-mark">
                    <i class="ti ti-users" aria-hidden="true"></i>
                </div>
                <span class="logo-text">RHMatch</span>
            </div>

            <div class="left-body">
                <div class="left-tag">
                    <i class="ti ti-sparkles" aria-hidden="true" style="font-size:12px"></i>
                    Psicometria + IA para RH
                </div>

                <h1 class="left-h1">Contrate com inteligência de dados</h1>

                <p class="left-sub">
                    Testes DISC, Eneagrama e 16 Personalidades aplicados direto na plataforma,
                    com match automático por IA.
                </p>
            </div>

            <div class="mockup-card">
                <div class="mc-label">Ranking — Gerente de produto</div>

                <div class="mc-row">
                    <div class="mc-avatar" style="background:rgba(255,255,255,.15);color:#fff">AS</div>
                    <span class="mc-name">Ana Souza</span>
                    <div class="mc-bar-wrap">
                        <div class="mc-bar" style="width:87%;background:#5DCAA5"></div>
                    </div>
                    <span class="mc-pct">87%</span>
                </div>

                <div class="mc-row">
                    <div class="mc-avatar" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.7)">CL</div>
                    <span class="mc-name">Carlos Lima</span>
                    <div class="mc-bar-wrap">
                        <div class="mc-bar" style="width:71%;background:#378ADD"></div>
                    </div>
                    <span class="mc-pct">71%</span>
                </div>

                <div class="mc-row">
                    <div class="mc-avatar" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.7)">BN</div>
                    <span class="mc-name">Beatriz Neves</span>
                    <div class="mc-bar-wrap">
                        <div class="mc-bar" style="width:54%;background:#85B7EB"></div>
                    </div>
                    <span class="mc-pct">54%</span>
                </div>
            </div>

            <div class="features-list">
                <div class="feat-item">
                    <div class="feat-icon">
                        <i class="ti ti-brain" aria-hidden="true"></i>
                    </div>
                    <span class="feat-text">Motor próprio de testes psicométricos</span>
                </div>

                <div class="feat-item">
                    <div class="feat-icon">
                        <i class="ti ti-chart-bar" aria-hidden="true"></i>
                    </div>
                    <span class="feat-text">Match candidato × vaga × líder × cultura</span>
                </div>

                <div class="feat-item">
                    <div class="feat-icon">
                        <i class="ti ti-link" aria-hidden="true"></i>
                    </div>
                    <span class="feat-text">Portal white-label para candidatos</span>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="login-switch">
                    <a href="{{ route('login') }}">
                        Entrar
                    </a>
                    <a href="{{ route('register') }}">
                        Registrar
                    </a>
            </div>
            
        </div>
    </div>
</body>
</html>