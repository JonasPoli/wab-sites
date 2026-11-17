#!/bin/bash
# Mede os 9 tenants do WAB Sites com o Lighthouse, em celular e computador.
#
#   sh/medir-pagespeed.sh antes
#   sh/medir-pagespeed.sh depois
#
# Grava um JSON por domínio e formato na pasta deste script. Compare com
# sh/comparar-pagespeed.php. A API publica do PageSpeed tem cota diaria e ja
# respondeu "Quota exceeded" no diagnostico, por isso a medicao e local; o motor
# e o mesmo do PageSpeed. A conferencia final para o cliente e no site oficial.
#
# Depois de um deploy, rode antes `bin/console app:miniaturas:aquecer` no
# servidor: medir com o cache de imagens frio nao vale.
set -u
PREFIX="${1:-antes}"
DIR="$(cd "$(dirname "$0")" && pwd)"
export CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
DOMS="marapossi.com.br pkwy.com.br cemporcentoexpresso.com.br imperiodainformatica.com.br site.abifer.com.br site.transterraararaquara.com.br site.petvida.com.br site.topdrim.com.br ajope.wab.com.br"
i=0
for d in $DOMS; do
  for ff in mobile desktop; do
    i=$((i+1))
    out="$DIR/$PREFIX-$d-$ff.json"
    # Aquecimento: o servidor tem 1 vCPU e, logo depois de um build, a primeira
    # resposta leva mais de um segundo só para compilar o template. Medir assim não
    # mede o site, mede o cache frio. Duas visitas antes de cada execução, e um
    # respiro entre elas para não medir o servidor saturado pela medição anterior.
    curl -s -o /dev/null -m 30 "https://$d/" || true
    sleep 2
    curl -s -o /dev/null -m 30 "https://$d/" || true
    sleep 3
    if [ "$ff" = "desktop" ]; then
      EMU="--preset=desktop"
    else
      EMU="--form-factor=mobile --screenEmulation.mobile"
    fi
    npx --yes lighthouse@12 "https://$d/" \
      --only-categories=performance,seo,accessibility,best-practices \
      $EMU --output=json --output-path="$out" \
      --chrome-flags="--headless=new --no-sandbox --disable-gpu --user-data-dir=/tmp/lh-profile-$i" \
      --quiet >/dev/null 2>&1
    echo "ok $d $ff"
  done
done
echo "FIM"
