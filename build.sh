#!/bin/bash
rm -rf var/cache

# Cores para o output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Detectar binário do PHP se não foi fornecido no ambiente
if [ -z "$PHP_BIN" ]; then
    if [ -f "/RunCloud/Packages/php84rc/bin/php" ]; then
        PHP_BIN="/RunCloud/Packages/php84rc/bin/php"
    elif [ -f "/opt/cpanel/ea-php83/root/bin/php" ]; then
        PHP_BIN="/opt/cpanel/ea-php83/root/bin/php"
    elif [ -f "/opt/cpanel/ea-php84/root/bin/php" ]; then
        PHP_BIN="/opt/cpanel/ea-php84/root/bin/php"
    elif command -v php84 &> /dev/null; then
        PHP_BIN="php84"
    elif command -v php83 &> /dev/null; then
        PHP_BIN="php83"
    else
        PHP_BIN="php"
    fi
fi

echo -e "${BLUE}==> Iniciando limpeza completa...${NC}"

# 1. Limpar Cache do Symfony
echo -e "${GREEN}--> Limpando cache do Symfony...${NC}"
$PHP_BIN bin/console cache:clear
rm -rf var/cache/*

# 2. Limpar Cache de Imagens (LiipImagine)
if [ -d "public/media/cache" ]; then
    echo -e "${GREEN}--> Removendo miniaturas (LiipImagine)...${NC}"
    rm -rf public/media/cache/*
fi

# 3. Recompilar Tailwind
echo -e "${GREEN}--> Recompilando Tailwind CSS...${NC}"
$PHP_BIN bin/console tailwind:build --minify

# 4. Compilar Assets (Asset Mapper)
echo -e "${GREEN}--> Compilando AssetMap...${NC}"
$PHP_BIN bin/console asset-map:compile

# 5. Limpar Logs
echo -e "${GREEN}--> Limpando logs...${NC}"
rm -rf var/log/*

# 6. Limpar Cache do Liip Imagine
$PHP_BIN bin/console liip:imagine:cache:remove

echo -e "${BLUE}==> Concluído com sucesso!${NC}"
