# Plano de Melhorias — PageSpeed Insights
**Relatório base:** 22/04/2026 · Desktop · Lighthouse 13.0.1  
**URL analisada:** https://cetec.wab.com.br/

| Métrica | Pontuação Atual | Meta |
|---|---|---|
| 🟠 Performance | 84 | ≥ 90 |
| 🟠 Acessibilidade | 79 | ≥ 90 |
| 🟢 Práticas Recomendadas | 100 | Manter |
| 🔴 SEO | 50 | ≥ 90 |

---

## ✅ imagens — IMPLEMENTADO

Utilizamos o `liip/imagine-bundle` (já instalado) para gerar imagens em WebP e em tamanhos corretos.

**O que foi feito:**
- Adicionados 7 `filter_sets` específicos no `liip_imagine.yaml` com qualidade ~80 e dimensões adequadas a cada slot visual
- Todos os templates públicos atualizados para usar `| imagine_filter(...)` em vez de `vich_uploader_asset` puro
- O bundle gera automaticamente variantes WebP em disco na primeira requisição (`webp.generate: true`)

| Filter Set | Tamanho | Uso |
|---|---|---|
| `cetec_banner_hero` | 1280×720 | Banner/hero da home |
| `cetec_curso_card` | 760×428 | Cards de cursos (home e listagem) |
| `cetec_curso_hero` | 1200×450 | Imagem principal de curso/notícia detalhe |
| `cetec_noticia_card` | 600×338 | Thumbnails de notícias |
| `cetec_depoimento_avatar` | 96×96 | Avatares de depoimentos |
| `cetec_estrutura` | 400×300 | Imagens de estrutura e linha do tempo |
| `cetec_parceira_logo` | 300×150 | Logos de empresas parceiras |

## 🔴 SEO (50 → 90+) — Prioridade CRÍTICA

### 1. Remover `X-Robots-Tag: noindex` da resposta HTTP

> O servidor está enviando `X-Robots-Tag: noindex` em todas as respostas, bloqueando completamente a indexação pelo Google.

**Causa provável:** a variável `APP_ENV=dev` ou uma configuração do servidor (RunCloud / nginx) está adicionando esse header indevidamente em produção.

**Ação:**
- Verificar configuração do nginx/apache no RunCloud e remover o header `X-Robots-Tag: noindex` em produção.
- Confirmar que `APP_ENV=prod` está definido no `.env.local` do servidor.
- Adicionar um meta tag explícito de index no `<head>` do `base.html.twig`:

```twig
{# templates/pub/base.html.twig — dentro do <head> #}
<meta name="robots" content="index, follow">
```

---

### 2. Criar `robots.txt` válido

> A URL `/robots.txt` retorna 404 — o Symfony não tem essa rota configurada.

**Arquivo a criar:** `public/robots.txt`

```
User-agent: *
Allow: /

Sitemap: https://cetec.wab.com.br/sitemap.xml
```

---

### 3. Criar `sitemap.xml`

Instalar e configurar o bundle `presta/sitemap-bundle` ou criar uma rota estática em `public/sitemap.xml` com as URLs das páginas públicas (home, cursos, notícias, sobre, contato).

---

### 4. Corrigir links não rastreáveis

> Dois links `href="file://..."` foram detectados — são gerados pela Symfony Web Debug Toolbar (WDT) e **não devem aparecer em produção**.

**Ação:** confirmar que a barra de debug está desabilitada no servidor (`APP_DEBUG=0` e `APP_ENV=prod`).

---

## 🟠 Acessibilidade (79 → 90+) — Prioridade ALTA

### 5. Botão de busca sem nome acessível

> `<button type="submit">` apenas com ícone Bootstrap Icons não tem texto alternativo para leitores de tela.

**Arquivo:** `templates/pub/base.html.twig` (linha 57)

```twig
{# Antes #}
<button type="submit" class="absolute right-2 text-gray-400 hover:text-cetec-orange">
    <i class="bi bi-search text-sm"></i>
</button>

{# Depois #}
<button type="submit" class="absolute right-2 text-gray-400 hover:text-cetec-orange" aria-label="Buscar">
    <i class="bi bi-search text-sm" aria-hidden="true"></i>
</button>
```

---

### 6. Links de redes sociais sem nome acessível

> Os 5 links do rodapé (Facebook, Instagram, LinkedIn, YouTube, WhatsApp) não têm texto compreensível.

**Arquivo:** `templates/pub/base.html.twig` (linhas 132–136)

```twig
{# Adicionar aria-label em cada link de rede social #}
<a href="https://facebook.com" target="_blank" aria-label="Facebook do CETEC" class="..."><i class="bi bi-facebook" aria-hidden="true"></i></a>
<a href="https://instagram.com" target="_blank" aria-label="Instagram do CETEC" class="..."><i class="bi bi-instagram" aria-hidden="true"></i></a>
<a href="https://linkedin.com" target="_blank" aria-label="LinkedIn do CETEC" class="..."><i class="bi bi-linkedin" aria-hidden="true"></i></a>
<a href="https://youtube.com" target="_blank" aria-label="YouTube do CETEC" class="..."><i class="bi bi-youtube" aria-hidden="true"></i></a>
<a href="https://wa.me/551633362414" target="_blank" aria-label="WhatsApp do CETEC" class="..."><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
```

---

### 7. Contraste insuficiente de texto

> Vários elementos com texto laranja (`#ff7f00`) sobre branco ou cinza claro não passam na razão de contraste mínima (4.5:1).

**Elementos afetados:** links do nav, botões CTA, textos em seções claras.

**Ação:** escurecer levemente o laranja para `#e06f00` (ou `#c66200`) apenas para texto, mantendo o laranja vivo como cor de fundo de botões. Ajustar em `app.css` ou nas variáveis CSS:

```css
/* Somente para texto sobre fundo claro */
.nav-link, .text-cetec-orange { color: #c96400; }
```

---

### 8. Hierarquia de headings incorreta

> O footer usa `<h3>` e `<h5>` sem passar por `<h4>`, e na home existe mais de um elemento que pode conflitar.

**Arquivo:** `templates/pub/base.html.twig` (linhas 162, 174, 185)

```twig
{# Antes #}
<h5 class="footer-title">Links Rápidos</h5>

{# Depois — usar h4 para subtítulos do rodapé #}
<h4 class="footer-title">Links Rápidos</h4>
```

E ajustar o CSS da classe `.footer-title` para manter o visual idêntico.

---

### 9. Links "Saiba Mais" repetidos com destinos diferentes

> Os cards de cursos têm múltiplos links com texto "Saiba Mais" apontando para URLs distintas.

**Arquivo:** `templates/pub/main/home.html.twig` (linha 123)

```twig
{# Antes #}
<a href="{{ path('pub_curso_detalhe', {id: curso.id}) }}" class="btn-cetec-outline text-sm py-2 px-5">Saiba Mais</a>

{# Depois — incluir nome do curso no aria-label #}
<a href="{{ path('pub_curso_detalhe', {id: curso.id}) }}" class="btn-cetec-outline text-sm py-2 px-5"
   aria-label="Saiba mais sobre {{ curso.titulo }}">Saiba Mais</a>
```

---

## 🟠 Performance (84 → 90+) — Prioridade MÉDIA-ALTA

### 10. Criar serviço de redimensionamento e conversão de imagens

> **Economia estimada: 1.530 KiB** — maior ganho individual disponível.  
> Imagens de depoimentos (~600×600px) servidas em slots de 48×48px; imagens de cursos (800×448px) em 379×284px.

**Estratégia:** Implementar um serviço Symfony que, ao salvar uma imagem via VichUploader, gera automaticamente versões redimensionadas em WebP usando a biblioteca `intervention/image` ou `liip/imagine-bundle`.

**Tamanhos alvo:**
- Depoimentos (avatar): 96×96px (2x para retina) em WebP
- Cursos (card): 760×427px em WebP  
- Banners (hero): 1920×1080px em WebP (com fallback JPEG)
- Logo: converter de PNG para SVG ou WebP < 5 KiB

**Template — uso de `<picture>` com srcset:**
```twig
{# templates/pub/main/home.html.twig — imagens de depoimentos #}
<picture>
    <source srcset="{{ vich_uploader_asset(dep, 'fotoFile')|replace({'.jpg':'-96.webp', '.png':'-96.webp'}) }}" type="image/webp">
    <img src="{{ vich_uploader_asset(dep, 'fotoFile') }}"
         alt="{{ dep.nome }}"
         width="48" height="48"
         class="w-12 h-12 rounded-full object-cover"
         style="border:2px solid #ff7f00"
         loading="lazy">
</picture>
```

---

### 11. Desbloquear CSS de renderização crítica

> **Economia estimada: 520ms** — Bootstrap Icons CSS, AOS CSS e Google Fonts estão no `<head>` bloqueando a renderização.

**Arquivo:** `templates/pub/base.html.twig`

**a) Carregar Bootstrap Icons CSS de forma assíncrona (apenas ícones não-críticos):**

```html
<!-- Antes -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Depois — carregamento assíncrono com fallback -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"></noscript>
```

**b) Self-host Bootstrap Icons** para eliminar a dependência de CDN externo e o WOFF2 de 128 KiB sendo carregado separadamente:

```bash
npm install bootstrap-icons
# Copiar bootstrap-icons.woff2 para public/fonts/
# Referenciar no app.css com @font-face local
```

**c) Carregar AOS CSS de forma não-bloqueante:**

```html
<!-- Antes -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

<!-- Depois -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
```

---

### 12. Aplicar `fetchpriority="high"` na imagem LCP (banner hero)

> A imagem do banner (LCP element) está como `background-image` CSS — não pode receber prioridade de fetch nativa.

**Ação:** Substituir o `background-image` inline por uma tag `<img>` com `fetchpriority="high"` e `loading="eager"`.

**Arquivo:** `templates/pub/main/home.html.twig` (linhas 10–16)

```twig
{# Antes — background-image inline #}
{% if banners|length > 0 and banners[0].imagem %}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
         style="background-image: url('{{ vich_uploader_asset(banners[0], 'imagemFile') }}')"></div>
{% endif %}

{# Depois — img tag com fetchpriority #}
{% if banners|length > 0 and banners[0].imagem %}
    <img src="{{ vich_uploader_asset(banners[0], 'imagemFile') }}"
         alt="{{ banners[0].titulo }}"
         class="absolute inset-0 w-full h-full object-cover object-center"
         fetchpriority="high"
         loading="eager"
         decoding="async"
         width="1920" height="1080">
{% endif %}
```

---

### 13. Adicionar `width` e `height` explícitos à tag de logo

> A imagem do logo não tem dimensões declaradas, causando CLS (layout shift) enquanto carrega.

**Arquivo:** `templates/pub/base.html.twig` (linhas 38 e 150)

```twig
{# Antes #}
<img src="{{ asset('asset/images/logo.png') }}" alt="CETEC Araraquara" class="h-10 w-auto">

{# Depois — adicionar width/height reais do arquivo (755×607) #}
<img src="{{ asset('asset/images/logo.png') }}" alt="CETEC Araraquara"
     class="h-10 w-auto" width="755" height="607">
```

> **Melhor ainda:** converter o logo PNG (37 KiB) para SVG inline ou um arquivo `.svg` — eliminaria totalmente o request e o CLS.

---

### 14. Adicionar `loading="lazy"` em imagens abaixo do fold

> Imagens de cursos, depoimentos e notícias carregam de imediato junto com o banner.

**Arquivo:** `templates/pub/main/home.html.twig`

```twig
{# Cursos (linha 113) — adicionar lazy #}
<img src="{{ vich_uploader_asset(curso, 'imagemPrincipalFile') }}"
     alt="{{ curso.titulo }}"
     class="course-card__img"
     loading="lazy"
     decoding="async">

{# Depoimentos (linha 157) — adicionar lazy #}
<img src="{{ vich_uploader_asset(dep, 'fotoFile') }}"
     alt="{{ dep.nome }}"
     class="w-12 h-12 rounded-full object-cover"
     style="border:2px solid #ff7f00"
     loading="lazy"
     decoding="async"
     width="48" height="48">
```

---

### 15. Minificar CSS (`app.css`)

> **Economia estimada: 2,5 KiB** — o arquivo CSS não está minificado.

**Ação:** Usar o Webpack Encore em modo produção e garantir que o build seja gerado com `--env production`:

```bash
# No servidor de produção
NODE_ENV=production npx encore production
```

Verificar se o `webpack.config.js` tem `.enableSourceMaps(false)` e `.cleanupOutputBeforeBuild()` ativos em produção.

---

### 16. Remover CSS não usado do Bootstrap Icons

> **Economia estimada: 13 KiB** — o CSS completo do Bootstrap Icons (~14 KiB) está carregado, mas apenas uma fração dos ícones é usada.

**Opção A (Recomendada):** Usar PurgeCSS via PostCSS no pipeline do Encore para remover classes de ícones não utilizadas.

**Opção B:** Substituir Bootstrap Icons pelo pacote `@symfony/ux-icons` (Iconify) e usar apenas os ícones necessários como SVG inline.

```twig
{# Em vez de <i class="bi bi-facebook"> #}
<twig:ux:icon name="bi:facebook" />
```

Isso elimina **completamente** a dependência CDN e o WOFF2 de 128 KiB.

---

### 17. Adicionar `preconnect` para o CDN do Bootstrap Icons

> O CDN `cdn.jsdelivr.net` não tem pré-conexão configurada, adicionando latência às requisições de CSS e fontes.

**Arquivo:** `templates/pub/base.html.twig` (logo após as preconnects existentes)

```html
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
```

---

### 18. Adicionar `font-display: swap` para Bootstrap Icons

> A fonte `bootstrap-icons.woff2` bloqueia a renderização de texto por 20ms enquanto carrega.

Se mantiver o carregamento via CDN, adicionar na query do CSS ou configurar no `app.css` com uma `@font-face` local com `font-display: swap`.

---

## 📋 Resumo de Tarefas por Ordem de Impacto

| # | Tarefa | Impacto | Esforço | Arquivo(s) |
|---|---|---|---|---|
| 1 | Remover `noindex` do servidor | 🔴 SEO crítico | Baixo | Servidor / `.env.local` |
| 2 | Criar `public/robots.txt` | 🔴 SEO crítico | Baixo | `public/robots.txt` |
| 3 | Conversão de imagens para WebP com resize | 🟠 Perf +1.530KiB | Alto | Serviço Symfony |
| 4 | LCP: substituir `background-image` por `<img fetchpriority>` | 🟠 Perf LCP | Baixo | `home.html.twig` |
| 5 | Carregar Bootstrap Icons / AOS de forma assíncrona | 🟠 Perf +520ms | Baixo | `base.html.twig` |
| 6 | `aria-label` em botão de busca e links sociais | 🟠 A11y | Baixo | `base.html.twig` |
| 7 | `loading="lazy"` em imagens abaixo do fold | 🟠 Perf | Baixo | `home.html.twig` |
| 8 | `width`/`height` explícitos em imagens | 🟡 CLS | Baixo | `base.html.twig`, `home.html.twig` |
| 9 | Contraste de cores (texto laranja) | 🟡 A11y | Médio | `app.css` |
| 10 | Hierarquia de headings (h5 → h4) | 🟡 A11y | Baixo | `base.html.twig` |
| 11 | `aria-label` nos links "Saiba Mais" | 🟡 A11y | Baixo | `home.html.twig` |
| 12 | Minificar CSS em produção (Encore) | 🟡 Perf +2.5KiB | Baixo | `webpack.config.js` |
| 13 | Substituir Bootstrap Icons por UX Icons (SVG) | 🟢 Longo prazo | Alto | Todos os templates |
| 14 | Criar sitemap.xml | 🟢 SEO | Médio | Bundle ou rota |
| 15 | Self-host fontes Google Fonts | 🟢 Longo prazo | Médio | `base.html.twig` + assets |

---

## ⚠️ Observações Adicionais

- **Symfony Toolbar em produção:** os links `file:///home/runcloud/...` e o request `/_wdt/...` na cadeia de dependências indicam que a **Web Debug Toolbar está ativa em produção**. Isso é um problema de segurança além de performance. Garantir `APP_ENV=prod APP_DEBUG=0` no servidor.
- **DOM muito grande (936 elementos):** causado principalmente pelos 50 depoimentos renderizados de uma vez na home. Considerar limitar a query a **6–9 depoimentos** no controller e adicionar paginação ou um slider.
- **Dados de dummy data:** as notas de SEO e tamanho de DOM são parcialmente ruins devido aos 50 registros fictícios gerados via `app:generate-dummy-data`. Em produção, o impacto será menor com dados reais e limitados.
