#!/bin/bash
# Fotografia do HTML público dos sites das fixtures, para provar que uma mudança não
# alterou o que já existia.
#
#   sh/regressao.sh capturar antes     # no commit anterior à mudança, fixtures recém-carregadas
#   sh/regressao.sh capturar depois    # depois da mudança, SEM recarregar as fixtures
#   sh/regressao.sh comparar
#   sh/regressao.sh renormalizar      # reaplica a normalização depois de uma regra nova de ruído
#
# Os ids de seção e bloco aparecem no HTML, então as fixtures não podem ser recarregadas
# entre as duas capturas. O que muda sozinho a cada pedido (token CSRF, hora assinada do
# contato, hash dos arquivos de CSS e JS, lastmod do sitemap, "há N minutos") é
# normalizado antes de gravar. Ruído novo vira regra aqui, nunca "ignorar arquivo".
#
# O servidor local roda em HTTPS (symfony server:start) e, neste disco exFAT, com
# APP_DEBUG=0. Hosts a mais podem ser passados em REGRESSAO_HOSTS.
set -u
cd "$(dirname "$0")/.." || exit 1

BASE_DIR="var/regressao"
HOSTS="${REGRESSAO_HOSTS:-127.0.0.1:8000 localhost:8000 noticias.localhost:8000}"
# "bot" no User-Agent: a notícia não conta visualização, e as "Mais lidas" não mudam entre capturas
UA="Mozilla/5.0 (compatible; regressao-bot/1.0)"

normalizar() {
  sed -E \
    -e 's/(name="_token"[^>]*value=")[^"]*/\1TOKEN/g' \
    -e 's/(value=")[^"]*("[^>]*name="_token")/\1TOKEN\2/g' \
    -e 's/(name="wab_t"[^>]*value=")[^"]*/\1HORA/g' \
    -e 's/(value=")[^"]*("[^>]*name="wab_t")/\1HORA\2/g' \
    -e 's/-[A-Za-z0-9_-]{7}\.(css|js|woff2|svg|png)/-HASH.\1/g' \
    -e 's/^[[:space:]]+//' \
    -e 's#<lastmod>[^<]*</lastmod>#<lastmod>DATA</lastmod>#g' \
    -e 's/([Hh])á ([0-9]+|N) (minuto|hora|segundo|dia)s?/\1á N \3/g' \
    -e 's/^(date|set-cookie|x-debug-token[a-z-]*|x-request-id):.*$/\1: X/Ig' \
    -e 's/nonce="[^"]*"/nonce="N"/g' \
  | perl -0pe 's/<div id="sfwdt.*?(?=<\/body>)//s'   # barra de depuração do Symfony (só no dev)
}

baixar() { # host caminho arquivo
  curl -sk -A "$UA" "https://$1$2" | normalizar > "$3"
}

capturar() {
  local modo="$1"
  local dir="$BASE_DIR/$modo"
  rm -rf "$dir"
  for host in $HOSTS; do
    local hdir="$dir/${host%%:*}"
    mkdir -p "$hdir"
    baixar "$host" "/" "$hdir/home.html"
    baixar "$host" "/sitemap.xml" "$hdir/sitemap.xml"
    baixar "$host" "/robots.txt" "$hdir/robots.txt"
    baixar "$host" "/noticias" "$hdir/noticias.html"
    curl -sk -A "$UA" -o /dev/null -w "%{http_code} %{redirect_url}\n" "https://$host/contato" > "$hdir/contato.status"
    curl -sk -A "$UA" -o /dev/null -w "%{http_code}\n" "https://$host/blog" > "$hdir/blog.status"
    curl -sk -A "$UA" -o /dev/null -w "%{http_code}\n" "https://$host/videos" > "$hdir/videos.status"
    # Páginas, categorias e notícias que o próprio sitemap lista
    command grep --color=never -oE '<loc>[^<]+</loc>' "$hdir/sitemap.xml" | sed -E 's#</?loc>##g' | while read -r url; do
      local caminho="/${url#*://*/}"
      [ "$caminho" = "/" ] && continue
      [ "${url#*://*/}" = "$url" ] && continue
      local nome
      nome="$(echo "$caminho" | sed -E 's#^/##; s#[/?=&]#_#g')"
      baixar "$host" "$caminho" "$hdir/$nome.html"
    done
    # Páginas que podem não estar no sitemap (sem slug publicado ainda)
    for extra in /p/todos-os-blocos /p/sobre; do
      local nome
      nome="$(echo "$extra" | sed -E 's#^/##; s#/#_#g')"
      [ -f "$hdir/$nome.html" ] || baixar "$host" "$extra" "$hdir/$nome.html"
    done
  done
  echo "Captura '$modo' gravada em $dir ($(find "$dir" -type f | wc -l | tr -d ' ') arquivos)"
}

case "${1:-}" in
  capturar)
    [ -n "${2:-}" ] || { echo "uso: $0 capturar <antes|depois>"; exit 2; }
    capturar "$2"
    ;;
  renormalizar)
    # Reaplica a normalização atual às capturas gravadas (depois de uma regra nova de ruído)
    find "$BASE_DIR" -type f \( -name '*.html' -o -name '*.xml' -o -name '*.txt' \) ! -name diferencas.txt | while read -r f; do
      normalizar < "$f" > "$f.tmp" && mv "$f.tmp" "$f"
    done
    echo "Capturas renormalizadas."
    ;;
  comparar)
    if diff -r "$BASE_DIR/antes" "$BASE_DIR/depois" > "$BASE_DIR/diferencas.txt"; then
      echo "Sem diferenças."
      rm -f "$BASE_DIR/diferencas.txt"
    else
      echo "Há diferenças (resumo abaixo; completo em $BASE_DIR/diferencas.txt):"
      grep -E '^(diff|Only in)' "$BASE_DIR/diferencas.txt"
      exit 1
    fi
    ;;
  *)
    echo "uso: $0 capturar <antes|depois> | renormalizar | comparar"; exit 2
    ;;
esac
