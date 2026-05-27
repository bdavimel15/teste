@echo off
title QueryBot — PHP Server
cd /d "%~dp0"

echo ============================================
echo   QueryBot — Painel de Testes
echo   http://localhost:8000
echo ============================================
echo.

where php >nul 2>&1
if errorlevel 1 (
    echo [ERRO] PHP nao encontrado no PATH.
    echo Instale o PHP e adicione ao PATH do sistema.
    pause
    exit /b 1
)

if not exist ".env" (
    echo [INFO] .env nao encontrado — copiando .env.example...
    copy /Y .env.example .env >nul 2>&1
    echo [OK] .env criado. Edite as variaveis antes de usar em producao.
    echo.
)

echo [OK] Iniciando servidor em http://localhost:8000
echo      Pressione Ctrl+C para parar.
echo.

php -S localhost:8000 -t public public/router.php
pause
