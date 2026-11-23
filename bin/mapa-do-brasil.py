#!/usr/bin/env python3
"""Gera templates/themes/_comum/_mapa_do_brasil.html.twig (bloco "Mapa de representantes").

Fonte: malha de UFs do IBGE, qualidade mínima, em GeoJSON:
  https://servicodados.ibge.gov.br/api/v3/malhas/paises/BR?formato=application/vnd.geo+json&qualidade=minima&intrarregiao=UF
Projeção equirretangular corrigida pelo cosseno da latitude média, largura 1000, e
simplificação de Douglas-Peucker (anéis fechados divididos no ponto mais distante).

Uso: python3 -I bin/mapa-do-brasil.py <br-uf.json> [tolerancia=1.5] > templates/themes/_comum/_mapa_do_brasil.html.twig
"""
import json, math, sys

UF = {'11': 'RO', '12': 'AC', '13': 'AM', '14': 'RR', '15': 'PA', '16': 'AP', '17': 'TO', '21': 'MA', '22': 'PI',
      '23': 'CE', '24': 'RN', '25': 'PB', '26': 'PE', '27': 'AL', '28': 'SE', '29': 'BA', '31': 'MG', '32': 'ES',
      '33': 'RJ', '35': 'SP', '41': 'PR', '42': 'SC', '43': 'RS', '50': 'MS', '51': 'MT', '52': 'GO', '53': 'DF'}


def aneis(g):
    return [g['coordinates']] if g['type'] == 'Polygon' else g['coordinates']


def dp(pts, tol):
    if len(pts) < 3:
        return pts
    (x1, y1), (x2, y2) = pts[0], pts[-1]
    dx, dy = x2 - x1, y2 - y1
    n = math.hypot(dx, dy) or 1e-9
    i, dm = 0, -1.0
    for j in range(1, len(pts) - 1):
        x, y = pts[j]
        dd = abs(dy * x - dx * y + x2 * y1 - y2 * x1) / n
        if dd > dm:
            i, dm = j, dd
    if dm > tol:
        return dp(pts[:i + 1], tol)[:-1] + dp(pts[i:], tol)
    return [pts[0], pts[-1]]


def main():
    d = json.load(open(sys.argv[1]))
    tol = float(sys.argv[2]) if len(sys.argv) > 2 else 1.5
    pts = [p for f in d['features'] for pol in aneis(f['geometry']) for ring in pol for p in ring]
    lon0, lon1 = min(p[0] for p in pts), max(p[0] for p in pts)
    lat0, lat1 = min(p[1] for p in pts), max(p[1] for p in pts)
    k = math.cos(math.radians((lat0 + lat1) / 2))
    esc = 1000.0 / ((lon1 - lon0) * k)
    h = round((lat1 - lat0) * esc)
    caminhos = {}
    for f in d['features']:
        partes = []
        for pol in aneis(f['geometry']):
            for ring in pol:
                q = [((p[0] - lon0) * k * esc, (lat1 - p[1]) * esc) for p in ring]
                m = max(range(len(q)), key=lambda j: math.hypot(q[j][0] - q[0][0], q[j][1] - q[0][1]))
                r = dp(q[:m + 1], tol)[:-1] + dp(q[m:], tol)
                if len(r) >= 4:
                    partes.append('M' + 'L'.join('%.1f,%.1f' % p for p in r[:-1]) + 'Z')
        caminhos[UF[f['properties']['codarea']]] = ''.join(partes)
    # DF por último, para ficar por cima de Goiás
    ordem = sorted(caminhos, key=lambda uf: (uf == 'DF', uf))
    print('{#')
    print('  Mapa do Brasil com os 27 estados, em SVG (bloco "Mapa de representantes", change')
    print('  bloco-mapa-de-representantes). GERADO por bin/mapa-do-brasil.py a partir da malha de UFs do')
    print('  IBGE (servicodados.ibge.gov.br, qualidade mínima): não editar à mão.')
    print('  Parâmetros: mapaId (id do bloco), contagem (UF => quantidade de registros), nomes (UF => nome).')
    print('  Cada estado é um link para o cartão dele (#mapa-<id>-<UF>, ou #mapa-<id>-vazio sem registro):')
    print('  funciona pelo teclado e sem JavaScript.')
    print('#}')
    print('{% set caminhos = {')
    print(',\n'.join("    %s: '%s'" % (uf, caminhos[uf]) for uf in ordem))
    print('} %}')
    print('<svg class="mapa-br" viewBox="0 0 1000 %d" role="group" aria-label="Mapa do Brasil: escolha um estado">' % h)
    print("{%- for uf, d in caminhos %}{% set n = contagem[uf] ?? 0 %}"
          "<a href=\"#mapa-{{ mapaId }}-{{ n ? uf : 'vazio' }}\" data-mapa-uf=\"{{ uf }}\" aria-label=\"{{ nomes[uf] }}: "
          "{{ n == 0 ? 'sem representante' : (n == 1 ? '1 representante' : n ~ ' representantes') }}\">"
          "<path class=\"mapa-uf {{ n ? 'tem' : 'sem' }}\" d=\"{{ d }}\"/></a>{% endfor -%}")
    print('</svg>')


main()
