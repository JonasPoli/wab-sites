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
