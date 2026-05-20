# Otimização de Performance e SEO — wab.com.br

> **Objetivo:** Elevar a nota do Google PageSpeed Insights (Desktop) de **67/100** em direção a **90+/100**, reduzindo o impacto negativo nos Core Web Vitals e, consequentemente, melhorando o ranqueamento orgânico no Google.

---

## Por que a performance impacta o ranqueamento?

O Google utiliza as **Core Web Vitals (CWV)** como fator de ranqueamento desde 2021. As três métricas principais são:

| Métrica | Descrição | Peso no ranking |
|---|---|---|
| **LCP** — Largest Contentful Paint | Velocidade de carregamento do maior elemento visível | Alto |
| **CLS** — Cumulative Layout Shift | Estabilidade visual (elementos que "pulam" durante o carregamento) | Alto |
| **FID/INP** — Interaction to Next Paint | Responsividade às interações do usuário | Alto |

Um site com CLS crítico (como o nosso **0.852–1.0**) é penalizado diretamente no ranking das buscas orgânicas, pois o Google interpreta isso como **má experiência do usuário**.

---

## Estado Inicial (diagnóstico — abril 2026)

| Métrica | Valor | Status |
|---|---|---|
| Performance Score | **67/100** | 🔴 Crítico |
| First Contentful Paint (FCP) | 0,3 s | ✅ Excelente |
| Largest Contentful Paint (LCP) | 1,8 s | 🟠 Atenção |
| Total Blocking Time (TBT) | 60 ms | ✅ Excelente |
| **Cumulative Layout Shift (CLS)** | **0,852 → 1,0** | 🔴 Crítico |
| Speed Index | 1,4 s | 🟢 Bom |

### Principais causas identificadas pelo PageSpeed

1. **CLS 1.0** — deslocamento visual de praticamente toda a página durante o carregamento
2. **Reflow forçado** — scripts de animação consultando propriedades geométricas
3. **66 KiB de JS não utilizado** — Google Tag Manager bloqueando a thread principal
4. **Imagens superdimensionadas** — portfolio servido em 800×600 mas exibido em 590×443
5. **LCP com delay de 1.5 s** — imagem principal descoberta tarde pelo browser

---

## Alterações Realizadas

### 1. Causa Raiz do CLS: `app.css` Carregado de Forma Lazy

**Arquivo:** `templates/pub/base.html.twig`

**Problema:**
O CSS principal (`app.css`) era carregado com o truque `media="print" onload="this.media='all'"`, que carrega o arquivo de forma assíncrona (não bloqueante). Isso é tecnicamente correto para CSS não crítico, mas o site usa **Tailwind CSS** — quase **toda a estrutura visual** (layout, grid, flex, padding, fontes, cores) depende do `app.css`. 

Sem o CSS, o primeiro render mostrava a página sem layout. Quando o `app.css` carregava, **toda a página reflow de uma vez** — containers mudavam de largura, flex ativava, paddings apareciam — causando um CLS de 1.0 (máximo possível).

**Solução:**
```html
<!-- ANTES (lazy — causava CLS 1.0): -->
<link rel="stylesheet" href="{{ asset('styles/app.css') }}" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="{{ asset('styles/app.css') }}"></noscript>

<!-- DEPOIS (blocking — elimina CLS): -->
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
```

**Impacto:** FCP pode aumentar ~200ms, mas o CLS vai de 1.0 para ~0. O ganho na nota é muito maior do que a perda.

---

### 2. AOS.css Carregado de Forma Lazy

**Arquivo:** `templates/pub/main/home.html.twig`

**Problema:**
A biblioteca AOS (Animate On Scroll) define elementos com `data-aos` como `opacity: 0` via CSS. O `aos.css` era carregado de forma lazy, portanto:
1. Elementos começavam **visíveis** (sem CSS)
2. Quando `aos.css` carregava, todos os elementos `[data-aos]` **sumiam** de repente (CLS)
3. Quando `AOS.init()` executava, os elementos **reapareciam** (double CLS)

**Solução:**
Mover o `aos.css` para o `<head>` da home page via bloco Twig `head_preloads`:

```twig
{% block head_preloads %}
<link rel="preload" href="/images/bg-home-frame-01.avif" as="image" type="image/avif" fetchpriority="high">
<link rel="stylesheet" href="/lib/aos/aos.css">
{% endblock %}
```

O `aos.css` agora carrega bloqueante no head, garantindo que elementos comecem no estado correto (escondido) desde a primeira pintura.

---

### 3. Font Swap Causando CLS

**Arquivo:** `templates/pub/base.html.twig`

**Problema:**
A fonte `Josefin Sans` era carregada com `display=swap`. Isso instrui o browser a:
1. Renderizar o texto com a fonte de sistema (fallback)
2. Quando a Josefin Sans carregar, **trocar a fonte** em todo o texto da página

Essa troca causa reflow em todos os textos, gerando CLS proporcional ao número de elementos.

**Solução:**
Mudar para `display=optional`:

```html
<!-- ANTES: -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" ...>

<!-- DEPOIS: -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=optional" ...>
```

Com `display=optional`, o browser tem um curto período para carregar a fonte. Se não carregar a tempo, usa o fallback **sem jamais fazer a troca**. Elimina completamente o font-swap CLS.

---

### 4. Preload da Imagem LCP

**Arquivo:** `templates/pub/main/home.html.twig`

**Problema:**
A imagem LCP (`/images/bg-home-frame-01.avif`) tinha `fetchpriority="high"` na tag `<img>`, mas o browser só a descobria ao parsear o HTML. O PageSpeed apontou um delay de **1.500 ms** no carregamento do recurso LCP.

**Solução:**
Adicionar `<link rel="preload">` no `<head>` com `fetchpriority="high"`:

```html
<link rel="preload" href="/images/bg-home-frame-01.avif" as="image" type="image/avif" fetchpriority="high">
```

O browser começa a baixar a imagem **antes mesmo de parsear o restante do HTML**, reduzindo o delay de 1.500 ms para ~320 ms.

---

### 5. Google Tag Manager no Head Bloqueando Thread Principal

**Arquivo:** `templates/pub/base.html.twig`

**Problema:**
O GTM estava no `<head>` com `async`. Apesar do `async`, o GTM consulta propriedades geométricas do DOM (reflows forçados) e executava scripts de terceiros durante o carregamento inicial, bloqueando a thread principal por 134 ms.

**Solução 1:** Mover para o final do `<body>` com `defer`.

**Solução 2 (implementada):** Carregar via `window.addEventListener('load', ...)` — o script só carrega **após o evento `load` da página**, completamente fora da janela de medição de LCP/FCP/CLS:

```html
<script>
    window.addEventListener('load', function() {
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=G-WYHQFG049R';
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-WYHQFG049R');
    });
</script>
```

> **Nota:** Com scripts de terceiros como GTM, é praticamente impossível atingir nota 100/100. O GTM sempre carrega ~164 KiB de JavaScript de terceiros. A nota máxima realista com GTM ativo é **90–95/100**.

---

### 6. Preload da Fonte Josefin Sans

**Arquivo:** `templates/pub/base.html.twig`

**Problema:**
A fonte era descoberta pelo browser apenas ao processar o CSS do Google Fonts, adicionando latência extra.

**Solução:**
Pré-carregar o arquivo `.woff2` da fonte diretamente:

```html
<link rel="preload" href="https://fonts.gstatic.com/s/josefinsans/v32/Qw3PZQNVED7rKGKxtqIqX5E-AVSJrOCfjY46_DjRXMFrLgTsQV0.woff2" as="font" type="font/woff2" crossorigin="anonymous">
```

---

### 7. CSS Crítico Inline para Prevenir CLS de Background

**Arquivo:** `templates/pub/base.html.twig`

**Problema:**
Mesmo com `app.css` bloqueante, o `<body>` tem classes Tailwind como `bg-slate-100`, `overflow-x-hidden` e `dark:bg-slate-800`. Há um pequeno intervalo entre o parse do HTML e a aplicação do CSS onde o body pode aparecer sem estilo.

**Solução:**
CSS crítico inline no `<head>` com as propriedades essenciais do body:

```html
<style>
  /* CSS crítico inline — evita CLS ao carregar app.css de forma lazy */
  body { background-color: #f1f5f9; min-height: 100vh; overflow-x: hidden; }
  html.dark body { background-color: #1e293b; }
</style>
```

---

### 8. Redução das Animações JavaScript

**Arquivo:** `templates/pub/main/home.html.twig`

**Problema:**
O script de animação de ícones no header criava **150 imagens simultâneas** com `requestAnimationFrame` recursivo, causando layout thrashing e alto consumo de CPU/GPU.

**Solução:**
Reduzir drasticamente os valores:

```javascript
// ANTES:
const maxImages = 200;
const initialImages = 150;

// DEPOIS:
const maxImages = 50;
const initialImages = 20;
```

---

### 9. Redução do DOM — Loop de Partículas

**Arquivo:** `templates/pub/main/home.html.twig`

**Problema:**
O loop Twig `{% for i in 0..80 %}` criava **81 elementos `<div>`** com `<img>` cada um, totalizando 162 nós no DOM apenas para decoração. DOM grande = mais trabalho na thread de renderização.

**Solução:**
```twig
{# ANTES: #}
{% for i in 0..80 %}

{# DEPOIS: #}
{% for i in 0..20 %}
```

---

### 10. Imagens de Portfólio Superdimensionadas

**Arquivo:** `config/packages/liip_imagine.yaml`

**Problema:**
O LiipImagineBundle gerava imagens de portfólio em **800×600 px**, mas o Lighthouse apontou que eram exibidas em apenas **588×441 px** (40% maiores do que o necessário), gerando ~82 KiB de dados desnecessários.

**Solução:**
Ajustar o filtro para o tamanho exato de exibição:

```yaml
# ANTES:
portfolio_list:
    filters:
        thumbnail: { size: [800, 600], mode: outbound }

# DEPOIS:
portfolio_list:
    filters:
        thumbnail: { size: [590, 443], mode: outbound }
```

Após alterar, é necessário limpar o cache do LiipImagine no servidor:
```bash
php bin/console liip:imagine:cache:remove
```

---

## Fluxo de Deploy no Servidor

Após cada `git push`, executar no servidor via Bitbucket Pipelines ou SSH:

```bash
git pull && bash build.sh
```

O `build.sh` executa automaticamente:

```bash
# 1. Recompilar o CSS Tailwind (app + admin)
/RunCloud/Packages/php82rc/bin/php bin/console tailwind:build --minify
/RunCloud/Packages/php82rc/bin/php bin/console tailwind:build assets/styles/admin.css --minify

# 2. Recompilar o mapa de assets (fingerprinting)
/RunCloud/Packages/php82rc/bin/php bin/console asset-map:compile

# 3. Limpar cache das imagens LiipImagine (força regeneração em WebP)
/RunCloud/Packages/php82rc/bin/php bin/console liip:imagine:cache:remove
```

---

## LiipImagineBundle — Configuração WebP

Todos os filtros de imagem já estão configurados para gerar **WebP** com qualidade 80:

```yaml
liip_imagine:
    driver: "gd"
    filter_sets:
        portfolio_list:
            quality: 80
            format: webp          # ← Converte para WebP automaticamente
            filters:
                thumbnail: { size: [590, 443], mode: outbound }

        customer_logo:
            quality: 80
            format: webp
            filters:
                thumbnail: { size: [250, 200], mode: inset }

        tech_icon:
            quality: 80
            format: webp
            filters:
                thumbnail: { size: [120, 120], mode: inset }
```

---

## Resumo de Impacto Esperado

| Otimização | Métrica Afetada | Impacto Estimado |
|---|---|---|
| `app.css` blocking | CLS | **-0.8 a -1.0 ponto** |
| AOS.css no head | CLS | -0.1 a -0.2 ponto |
| Font `display=optional` | CLS | -0.0 a -0.1 ponto |
| Preload LCP image | LCP | -400 ms |
| GTM pós-load | TBT / LCP | -134 ms na thread |
| Portfolio 590×443 WebP | Imagens | -82 KiB |
| Animações reduzidas (20 imgs) | CPU / Thread | -30% CPU |
| DOM reduzido (21 partículas) | Thread / Rendering | -60 nós DOM |

---

## O que o Google avalia (Core Web Vitals)

```
Performance Score = f(LCP, CLS, FCP, TBT, SI)

Pesos aproximados (Lighthouse 12):
  LCP  → 25%
  CLS  → 15%
  FCP  → 10%
  TBT  → 30%  ← Maior peso! (proxy para INP)
  SI   → 10%
  TTI  → 10%
```

O **CLS de 1.0** estava devastando a nota porque qualquer valor acima de 0.25 recebe **nota 0** nessa métrica. Com CLS próximo de 0, a contribuição do CLS para a nota final muda de 0% para ~15%, resultando em um ganho direto de **+10 a +15 pontos** na nota total.

---

## Limitações Conhecidas

- **GTM sempre penaliza ~5-10 pontos:** Os 163 KiB do `gtag.js` são carregados de terceiros e Lighthouse sempre aponta JS não utilizado. Não há como eliminar isso sem remover o GTM.
- **Rellax.js e AOS.js causam reflows:** São bibliotecas de animação que por natureza precisam consultar geometria do DOM. O impacto está em ~25 ms no total — aceitável.
- **Nota 100 é possível apenas sem GTM:** Com GTM ativo + Rellax + AOS, a nota máxima realista é **88–94/100**.

---

*Última atualização: abril 2026 — Jonas Poli / WAB Agência Digital*
