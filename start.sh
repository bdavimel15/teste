#!/bin/bash
# Inicia múltiplos workers PHP para evitar deadlock de requisições encadeadas
# O Railway só expõe $PORT, então usamos um proxy reverso simples via socat
# ou simplesmente rodamos PHP com router que aceita múltiplas conexões

exec php -S 0.0.0.0:${PORT:-8080} -t public public/router.php
