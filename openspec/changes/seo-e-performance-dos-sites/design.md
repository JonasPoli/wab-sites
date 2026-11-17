# Design

## Context

Ver `proposal.md — Why` para a motivação e os números medidos em 17/11/2026.

O que molda a solução:

- **Um `<head>` por tema, hoje copiado quatro vezes.** `cetec`, `noticias`, `moderno` e `wab` repetem os mesmos blocos Twig (`title`, `meta_description`, `og_tags`, `canonical`, `robots`), cada um com um conjunto diferente do que falta. Os 8 sites de cliente usam o `cetec`; o ajope usa o `noticias` e, nas páginas institucionais, inclui os blocos do `cetec` — então mexer num bloco do `cetec` mexe nos dois temas.
- **Dois caminhos para a mesma página.** Com o Modo Landing Page ligado (os 9 tenants), `/` renderiza `page.html.twig` com a página de `Tenant.homePage`, e essa mesma página também responde em `/p/{slug}`. Qualquer regra de canônico e de sitemap precisa tratar esse par.
- **A biblioteca de miniaturas já existe e já foi resolvida uma vez.** O `liip_imagine.yaml` usa o `format_extension` como resolver padrão, o que faz o Twig escrever a URL `.webp` direto no HTML e elimina o 302 do LiipImagine (`docs/pagespeed-cetec.md`). Só o tema `noticias` usa, pelo filtro `news_image`, que já desvia para o arquivo original quando o GD do servidor não lê o formato (caso AVIF). O tema `cetec` não usa nada.
- **Servidor pequeno.** 1 vCPU e 2 GB. Gerar miniatura é caro e o `build.sh` apaga `public/media/cache/*` a cada deploy. PHP 8.3 no pool do site, GD sem AVIF, sem Imagick e sem conversor externo.
- **Ícone é dado do cliente.** As classes do FontAwesome (`fa-solid fa-building`) e do Bootstrap Icons são escolhidas no painel e gravadas no banco: de 6 a 18 por site nos 9 tenants. Não dá para decidir em tempo de compilação quais existem.

## Goals / Non-Goals

**Goals:**

- Uma única fonte de verdade para as metas e o JSON-LD, usada pelos quatro temas, de modo que página nova nasça com SEO certo.
- Nenhuma requisição de renderização para domínio de terceiro.
- Nenhuma `<img>` apontando para `/uploads/` numa página pública.
- Medição repetível: o mesmo comando mede os 9 domínios antes e depois, e o resultado é comparável.

**Non-Goals:**

- Trocar o construtor de páginas, o modelo de blocos ou os temas. Nada do que o cliente vê muda de lugar.
- Mexer nos 4 domínios-raiz servidos por outros sistemas.
- Reescrever o conteúdo dos sites. Descrição de SEO em branco é resolvida por cascata automática, não por redação manual tenant a tenant.
- Cache de página HTML (Varnish, `Cache-Control` público no HTML). A página é por tenant e tem flash de sessão; fica fora.

## Decisions

### 1. Um serviço de SEO que devolve um objeto, e um parcial de `<head>` único

Um serviço (`src/Service/PageSeo.php`) recebe o contexto da página (tenant, página, categoria, notícia, autor, paginação) e devolve um objeto com título, descrição, canônico, imagem, robots e o grafo JSON-LD já montado. Um parcial Twig (`templates/pub/_seo_head.html.twig`) imprime tudo e é incluído pelos quatro temas.

*Por quê:* hoje a regra está espalhada em 12 templates e cada um esqueceu algo diferente. Com o serviço, a cascata da descrição é testável em PHPUnit sem renderizar HTML, e o próximo tema herda o comportamento.

*Alternativa descartada:* resolver nos blocos Twig de cada tema, com `{% block meta_description %}` mais completo. Mantém a duplicação e não dá para testar a cascata isoladamente.

### 2. Cascata da descrição derivada do conteúdo, nunca vazia

Ordem: `Page.seoDescription` → `Tenant.seoDescription` → primeiro texto útil do conteúdo (resumo da notícia; descrição da categoria; primeiro bloco de texto da página, sem HTML) → `Tenant.name` mais o complemento de SEO do site. O corte é em 160 caracteres, na fronteira de palavra.

*Por quê:* nenhum dos 9 tenants preencheu o campo, e pedir que preencham antes de medir deixaria a entrega dependendo de conteúdo do cliente. A quarta origem garante que a auditoria `meta-description` nunca reprove; as três primeiras garantem que quem preenche ganha uma descrição melhor.

*Risco aceito:* a descrição automática é pior que uma escrita à mão. O campo continua no painel e, preenchido, vence.

### 3. Ícones viram SVG embutido, com os usados gravados no repositório

Entra o `symfony/ux-icons`. Os ~31 ícones fixos dos temas passam a `ux_icon('bi:whatsapp')` e são gravados por `ux:icons:lock`. Para os ícones escolhidos no painel, um filtro Twig traduz a classe gravada (`fa-solid fa-building`) para o nome do Iconify (`fa6-solid:building`) e um comando novo (`app:icones:sincronizar`) varre os ícones em uso em todos os tenants e baixa os SVG para o repositório, para o servidor nunca buscar nada em tempo de renderização. Ícone que não existe no repositório não é desenhado e é registrado no log.

*Por quê:* troca ~110 KB de CSS mais duas famílias de fonte de ícone, vindas de dois CDNs, por alguns KB de SVG dentro do HTML já comprimido. Resolve de uma vez `render-blocking-resources`, parte de `unused-css-rules` e `font-display`.

*Alternativa descartada:* hospedar o CSS do FontAwesome no próprio domínio. Tira o terceiro domínio, mas mantém 100 KB de CSS bloqueante para desenhar 8 ícones.

*Alternativa descartada:* baixar à mão os SVG para `assets/icons/` sem o pacote. Evita a dependência, mas reimplementa o cache, o `lock` e a função Twig que o pacote já traz, num projeto cujo `CLAUDE.md` já aponta o `ux-icons` como a ferramenta de ícones.

### 4. Fontes servidas pelo próprio domínio, com `font-display: swap`

Um comando (`app:fontes:sincronizar`) baixa, uma vez, os arquivos `woff2` das 6 famílias do catálogo (`HeadingFonts`) para `assets/fonts/` e gera `assets/styles/fontes.css` com os `@font-face`. O `tenant_css_vars()` para de emitir `@import url('https://fonts.googleapis.com/...')` e passa a só nomear a família; o tema dá `preload` apenas nas famílias que aquele tenant usa.

*Por quê:* o `@import` dentro de um `<style>` em linha é o pior caso — o navegador só descobre a fonte depois de ler o CSS, não dá para dar `preload`, e `display=optional` deixa o título trocar de fonte depois da pintura. Servindo do próprio domínio some uma conexão para dois domínios do Google e o texto nunca fica invisível.

*Trade-off:* ~250 KB de `woff2` entram no repositório, e uma família nova no catálogo exige rodar o comando de novo.

### 5. Carrossel local e animação sem biblioteca

O Swiper passa a vir do `importmap` (AssetMapper) e é carregado só em página que tem carrossel, decidido no servidor por uma variável Twig que os templates de banner, galeria e depoimentos ligam. O AOS sai inteiro: as animações de entrada viram ~40 linhas de CSS mais um `IntersectionObserver`, lendo os mesmos atributos `data-aos` que já estão nos templates e no conteúdo gravado.

*Por quê:* o AOS custa um CSS e um JS de um terceiro domínio para um efeito que o navegador faz sozinho. Manter o atributo `data-aos` evita ter de reescrever os templates e o que já está gravado nos blocos.

### 6. Miniaturas: um filtro só, com os tamanhos reais de cada uso

O `news_image` vira `thumb`, geral, mantendo o desvio para o arquivo original quando o servidor não converte o formato. Um conjunto de filtro por uso (`banner_desktop`, `banner_mobile`, `capa_pagina`, `bloco_imagem`, `galeria`, `equipe`, `avatar`, `logo_parceiro`, `logo_site`, `fundo_secao`, `capa_noticia`, `capa_noticia_mobile`), todos em WebP, com largura igual ao dobro da maior largura de exibição. Onde a imagem aparece em larguras diferentes, o template usa `<picture>` com `srcset`.

*Por quê:* é a parte que explica a maior fatia do problema — 17,9 MB numa home. O LiipImagine já está configurado, com o resolver que evita o 302; falta usá-lo.

*Detalhe de produção:* o `build.sh` apaga `public/media/cache/*`, então o primeiro acesso depois do deploy gera tudo num servidor de 1 vCPU. A conferência inclui um passo de aquecimento (abrir as 9 homes) antes de medir.

### 7. Fundo de seção deixa de ser `background-image` em CSS em linha

Onde hoje o fundo da seção é `style="background-image:url(/uploads/...)"`, passa a ser uma `<img>` posicionada atrás do conteúdo, com miniatura, `width`/`height` e prioridade.

*Por quê:* imagem em CSS em linha não é descoberta cedo pelo navegador, não aceita `srcset` e não conta como elemento com tamanho declarado — é uma das causas de `lcp-discovery-insight` e `unsized-images` reprovarem.

### 8. Cache dos estáticos no Nginx, não no PHP

`/assets` e `/media/cache` ganham `expires 1y` e `Cache-Control: public, immutable`; `/uploads` fica em 30 dias. É alteração no Nginx do servidor wabsites — **escrita em produção, só com aprovação do Jonas**, feita no arquivo do webapp no RunCloud e com o arquivo anterior salvo antes.

*Por quê:* os dois primeiros têm impressão digital no nome (AssetMapper e o hash do LiipImagine), então não há risco de servir conteúdo velho. `/uploads` não tem: o nome é mantido quando o cliente troca a imagem, e por isso fica em 30 dias.

### 9. Medição por Lighthouse local, confirmação pelo PageSpeed oficial

Um script (`sh/medir-pagespeed.sh`) roda o Lighthouse nos 9 domínios, em celular e computador, e grava os JSON. A conferência final é o PageSpeed Insights oficial, com print.

*Por quê:* a API pública do PageSpeed tem cota diária e já respondeu "Quota exceeded" durante o diagnóstico; o Lighthouse local usa o mesmo motor e permite repetir a medição a cada ajuste. A nota do PageSpeed é a que vale para o cliente, e é a que vai no ticket.

## Risks / Trade-offs

- **Miniatura muda o enquadramento de uma imagem que o cliente escolheu** → os cortes usam `inset` (cabe inteira) onde a imagem é logo, avatar ou foto de equipe, e `outbound` (preenche) só onde o template já recorta por CSS hoje. Conferência visual das 9 homes, antes e depois, lado a lado.
- **Servidor de 1 vCPU gerando 150 miniaturas no primeiro acesso depois do deploy** → deploy fora do horário comercial e aquecimento das 9 homes logo em seguida, antes de qualquer medição; o LiipImagine grava em disco e só gera uma vez.
- **`data-aos` gravado no conteúdo dos blocos deixar de animar** → o CSS e o observador novos leem os mesmos atributos; conferência nas páginas que têm bloco com animação.
- **Ícone escolhido no painel que o comando de sincronização não baixou** → não desenha nada em vez de quebrar o layout, e o log registra. O comando é rodado de novo depois de qualquer cadastro de ícone novo.
- **Descrição automática ruim em página com pouco texto** → aceito; o campo do painel continua vencendo quando preenchido, e a lista dos tenants sem descrição vai no ticket para a equipe preencher depois.
- **100 em Desempenho no celular num servidor de 1 vCPU é apertado** → o alvo é atingível porque o que reprova hoje é imagem, CSS de terceiro e cache, tudo sob controle do projeto; se algum domínio ficar abaixo depois de todas as medidas, o resultado é relatado como está, com o motivo medido, em vez de declarado como 100.

## Migration Plan

1. Implementar e conferir em `localhost` e `127.0.0.1` com as fixtures, e em `noticias.localhost` para o tema do portal.
2. `git push origin main`.
3. No servidor wabsites, como `runcloud`, com aprovação do Jonas: backup do Nginx do webapp, `git pull`, `composer install`, `./build.sh`.
4. Ajustar o Nginx (`expires`) e recarregar.
5. Aquecer: abrir as 9 homes e as páginas internas principais.
6. Medir os 9 domínios e comparar com o diagnóstico de 17/11/2026.

**Rollback:** `git revert` do merge e `./build.sh`; o arquivo do Nginx volta pelo backup. Nada no banco muda, então não há migration para desfazer.

## Open Questions

- Para cada tenant, qual texto o cliente quer como descrição de SEO da home. Não trava a entrega: a cascata resolve agora e o campo pode ser preenchido depois sem mexer em código.
