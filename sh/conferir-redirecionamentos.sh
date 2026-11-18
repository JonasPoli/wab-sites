#!/bin/bash
# Confere se cada endereço de um ou mais sitemaps do site antigo chega a 200 no site novo
# (direto ou por redirecionamento).
#
#   sh/conferir-redirecionamentos.sh https://pichoneri.wab.com.br openspec/changes/site-marcenaria-pichoneri/referencia/*-sitemap.xml
#
# CURL_OPTS extra (ex.: CURL_OPTS=-k para um certificado ainda não emitido).
set -u
BASE="${1:?uso: $0 <base-url> <sitemap.xml>...}"; shift
ok=0; falhas=0
for mapa in "$@"; do
  for url in $(command grep --color=never -oE '<loc>[^<]+</loc>' "$mapa" | sed -E 's#</?loc>##g'); do
    caminho="/${url#*://*/}"
    [ "${url#*://*/}" = "$url" ] && caminho="/"
    final=$(curl -s ${CURL_OPTS:-} -A "Mozilla/5.0 (compatible; conferencia-bot)" -o /dev/null -L --max-redirs 5 -w '%{http_code} %{url_effective}' "$BASE$caminho")
    if [ "${final%% *}" = "200" ]; then ok=$((ok+1)); else falhas=$((falhas+1)); echo "FALHA $caminho -> $final"; fi
  done
done
echo "OK: $ok  Falhas: $falhas"
[ "$falhas" -eq 0 ]
