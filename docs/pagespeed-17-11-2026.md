# Diagnóstico de SEO e desempenho — 17/11/2026

Medição dos 9 tenants que o WAB Sites serve, antes da change `seo-e-performance-dos-sites`.
Ticket interno da WAB: **#13467**.

## Escopo

O sistema serve estes 9 domínios:

| Domínio | Tema | Observação |
|---|---|---|
| marapossi.com.br | cetec | |
| pkwy.com.br | cetec | |
| cemporcentoexpresso.com.br | cetec | |
| imperiodainformatica.com.br | cetec | |
| site.abifer.com.br | cetec | a raiz `abifer.com.br` é outro site (XHTML antigo, outro servidor) |
| site.transterraararaquara.com.br | cetec | a raiz é outro site (jQuery 1.3, outro servidor) |
| site.petvida.com.br | cetec | a raiz é WordPress no wab2 |
| site.topdrim.com.br | cetec | a raiz é WordPress 7.1.4 no wab2 |
| ajope.wab.com.br | noticias | portal da Associação dos Jornalistas Espíritas |

Os quatro domínios-raiz citados **não são deste sistema** e ficaram fora da change.

## Notas antes (Lighthouse local, celular / computador)

Ordem: desempenho / acessibilidade / práticas recomendadas / SEO.

| Domínio | Celular | Computador |
|---|---|---|
| ajope.wab.com.br | 77 / 100 / 100 / 92 | 88 / 100 / 100 / 92 |
| cemporcentoexpresso.com.br | 69 / 88 / 100 / 92 | 76 / 88 / 100 / 92 |
| imperiodainformatica.com.br | 71 / 90 / 100 / 92 | 75 / 90 / 100 / 92 |
| marapossi.com.br | 92 / 88 / 100 / 92 | 73 / 88 / 100 / 92 |
| pkwy.com.br | 71 / 86 / 100 / 92 | 74 / 86 / 100 / 92 |
| site.abifer.com.br | 71 / 80 / 100 / 92 | 75 / 73 / 100 / 92 |
| site.petvida.com.br | 71 / 85 / 100 / 92 | 84 / 85 / 100 / 92 |
| site.topdrim.com.br | 70 / 80 / 96 / 92 | 77 / 80 / 96 / 92 |
| site.transterraararaquara.com.br | 70 / 88 / 100 / 92 | 82 / 88 / 100 / 92 |

No PageSpeed Insights oficial, no mesmo dia: `imperiodainformatica.com.br` 74 de desempenho
no celular e `marapossi.com.br` 77 no computador, os dois com SEO 92.

## Peso de imagem da home, antes

| Domínio | Imagens | Total | Maior arquivo |
|---|---|---|---|
| site.abifer.com.br | 30 | 17,9 MB | 3,0 MB |
| site.topdrim.com.br | 10 | 15,6 MB | 2,2 MB |
| cemporcentoexpresso.com.br | 13 | 13,2 MB | 2,1 MB |
| site.petvida.com.br | 23 | 8,1 MB | 2,1 MB |
| pkwy.com.br | 5 | 7,3 MB | 2,0 MB |
| marapossi.com.br | 9 | 5,9 MB | 2,2 MB |
| site.transterraararaquara.com.br | 23 | 4,5 MB | 2,1 MB |
| imperiodainformatica.com.br | 5 | 3,9 MB | 2,1 MB |
| ajope.wab.com.br | 2 | 0,2 MB | 0,2 MB |

Todas em PNG, em tamanho original: o tema `cetec` não usava nenhum filtro da biblioteca de
miniaturas, que já estava configurada no projeto.

## Auditorias reprovadas (nas 18 execuções, salvo indicação)

**SEO** — `meta-description` (18/18): nenhuma home tinha descrição.

**Acessibilidade** — `color-contrast` (16), `heading-order` (16), `link-in-text-block` (16),
`frame-title` (10, iframe do mapa), `target-size` (6), `button-name` (4).

**Práticas recomendadas** — `errors-in-console` (2, no `site.topdrim.com.br`).

**Desempenho** — `modern-image-formats`, `uses-responsive-images`, `unsized-images`,
`image-delivery-insight`, `render-blocking-resources`, `render-blocking-insight`,
`font-display-insight`, `lcp-discovery-insight`, `network-dependency-tree-insight`,
`unused-css-rules`, `unused-javascript`, `legacy-javascript`, `uses-long-cache-ttl`,
`total-byte-weight` (16) e `cache-insight`, todas em 18/18.

## Outros problemas encontrados na leitura do código e da produção

- `public/robots.txt` era o mesmo texto de exemplo nos 9 sites, com a linha `Sitemap:`
  comentada.
- O `/sitemap.xml` do ajope listava 3 URLs e nenhuma notícia; o dos sites de cliente repetia
  a home como `/p/home` e não trazia categorias.
- Nenhuma página tinha dados estruturados (JSON-LD).
- O Open Graph tinha só `og:site_name` e `og:type`.
- Cada página carregava 4 folhas de estilo de 3 domínios de terceiro e o Google Fonts por
  `@import` dentro de um `<style>` em linha.
- `/favicon.ico` respondia 404 em todos os domínios.

## Como medir de novo

```bash
sh/medir-pagespeed.sh antes     # ou depois
```

O resultado fica em arquivos JSON, um por domínio e formato.
