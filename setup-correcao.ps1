# =====================================================
# SETUP AUTOMÁTICO - RHMatch (Correção Completa)
# Versão otimizada para Windows + Laravel 11
# =====================================================

Write-Host "🚀 Iniciando Setup Automático do RHMatch..." -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan

# Verifica se está na raiz correta
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Erro: Execute este script na raiz do projeto (pasta que contém o arquivo 'artisan')" -ForegroundColor Red
    pause
    exit 1
}

# 1. Remove a migration duplicada (causa do erro que você teve)
$oldMigration = "database\migrations\2026_05_09_070000_create_companies_table.php"
if (Test-Path $oldMigration) {
    Write-Host "🗑️  Removendo migration duplicada antiga..." -ForegroundColor Yellow
    Remove-Item $oldMigration -Force
    Write-Host "✅ Migration duplicada removida!" -ForegroundColor Green
} else {
    Write-Host "✅ Nenhuma migration duplicada encontrada." -ForegroundColor Gray
}

# 2. Limpa caches antigos
Write-Host "`n🧹 Limpando caches do Laravel..." -ForegroundColor Cyan
php artisan optimize:clear

# 3. Executa migrate:fresh (cria a tabela companies correta)
Write-Host "`n🗄️  Executando php artisan migrate:fresh..." -ForegroundColor Cyan
php artisan migrate:fresh --seed

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Migrações executadas com sucesso!" -ForegroundColor Green
} else {
    Write-Host "❌ Erro no migrate. Verifique a saída acima." -ForegroundColor Red
    pause
    exit 1
}

# 4. Cria o symlink do storage (ESSENCIAL para a logo funcionar)
Write-Host "`n🔗 Criando symlink do storage (php artisan storage:link)..." -ForegroundColor Cyan
php artisan storage:link

# 5. Cria pastas de upload
Write-Host "`n📁 Criando pastas de logos e currículos..." -ForegroundColor Cyan
New-Item -Path "storage\app\public\logos" -ItemType Directory -Force | Out-Null
New-Item -Path "storage\app\public\curriculos" -ItemType Directory -Force | Out-Null
Write-Host "✅ Pastas criadas com sucesso" -ForegroundColor Green

# 6. Dependências NPM + Build
Write-Host "`n📦 Verificando frontend..." -ForegroundColor Cyan
if (-not (Test-Path "node_modules")) {
    Write-Host "   Instalando dependências NPM..." -ForegroundColor Yellow
    npm install --no-audit
}
Write-Host "   Buildando assets (CSS + JS)..." -ForegroundColor Yellow
npm run build

# 7. Limpeza final
Write-Host "`n🧼 Limpando caches finais..." -ForegroundColor Cyan
php artisan config:clear
php artisan route:clear
php artisan view:clear

Write-Host "`n===================================================" -ForegroundColor Cyan
Write-Host "🎉 SETUP AUTOMÁTICO CONCLUÍDO COM SUCESSO!" -ForegroundColor Green
Write-Host "`n✅ Próximos passos:" -ForegroundColor Yellow
Write-Host "   1. Abra o arquivo .env e configure:" -ForegroundColor White
Write-Host "      GROQ_API_KEY=sua_chave_aqui" -ForegroundColor White
Write-Host "      APP_URL=http://localhost:8000" -ForegroundColor White
Write-Host "`n   2. Rode o servidor:" -ForegroundColor White
Write-Host "      php artisan serve" -ForegroundColor White
Write-Host "`n   3. Acesse: http://localhost:8000/onboarding" -ForegroundColor White
Write-Host "`nTeste o upload da logo na Etapa 1!" -ForegroundColor Magenta
Write-Host "`nQualquer erro durante a execução, copie toda a saída e cole aqui que eu resolvo na hora." -ForegroundColor Cyan

Write-Host "`nPressione qualquer tecla para finalizar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")