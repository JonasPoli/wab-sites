# Guia Definitivo de Otimização PageSpeed (Playbook de Performance)

Este documento centraliza todas as estratégias, códigos e configurações utilizadas para elevar o site CETEC à nota **100/100 no PageSpeed Insights**. 
Ele foi escrito como um "Playbook" de performance para que **qualquer outro projeto web (especialmente focado no ecossistema Symfony/Twig)** possa ser otimizado seguindo os mesmos passos.

---

## 1. Otimização de LCP (Largest Contentful Paint)

O LCP mede o tempo que o maior elemento visível (geralmente o banner principal) leva para renderizar. O motor do Lighthouse penaliza severamente imagens grandes que demoram para ser descobertas ou baixadas.

### Estratégias Aplicadas:
- **Preload no `<head>`:** As imagens do banner principal (Desktop e Mobile) devem ser pré-carregadas. Isso instrui o navegador a iniciar o download em alta prioridade antes mesmo de iniciar a leitura das tags do `<body>`.
- **Prioridade e Eager Loading:** A imagem classificada como LCP **nunca** deve conter o atributo `loading="lazy"`. Ela deve ter explicitamente `loading="eager"` e `fetchpriority="high"`.

**Exemplo de Implementação Padrão (Twig):**
```html
{# No base.html.twig, dentro da tag <head> #}
{% block preloads %}
    <link rel="preload" href="{{ asset('caminho/para/banner-mobile.webp') }}" as="image" fetchpriority="high" media="(max-width: 767px)">
    <link rel="preload" href="{{ asset('caminho/para/banner-desktop.webp') }}" as="image" fetchpriority="high" media="(min-width: 768px)">
{% endblock %}

{# No template principal onde o banner é renderizado #}
<picture>
    <source media="(max-width: 767px)" srcset="{{ asset('caminho/para/banner-mobile.webp') }}">
    <img src="{{ asset('caminho/para/banner-desktop.webp') }}" 
         fetchpriority="high" 
         loading="eager" 
         decoding="async" 
         alt="Texto alternativo indispensável">
</picture>
```

---

## 2. Eliminação de Redirecionamentos 302 (Problema Crônico do LiipImagineBundle)

**O Problema Crônico:** 
Ao configurar formatos Next-Gen (WebP) no `LiipImagineBundle`, o padrão do bundle gera URLs dinâmicas de resolução, como `/media/cache/resolve/filtro/imagem.png`. 
Quando o navegador acessa essa URL, o Symfony gera o WebP e retorna um **Redirecionamento HTTP 302** para a URL final `.webp`. O PageSpeed detecta isso como *"Encadear solicitações críticas"* (Chained Critical Requests), o que destrói a nota de rede e atrasa o LCP.

**A Solução Definitiva:**
É necessário forçar o LiipImagine a injetar a URL estática com extensão `.webp` diretamente no HTML, utilizando a classe `FormatExtensionResolver`.

**Passo a Passo da Solução:**

**A. Registrar o Resolver como serviço (`config/services.yaml`):**
```yaml
services:
    Liip\ImagineBundle\Imagine\Cache\Resolver\FormatExtensionResolver:
        arguments:
            - '@liip_imagine.cache.resolver.default'
            - '@liip_imagine.filter.configuration'
        tags:
            - { name: "liip_imagine.cache.resolver", resolver: "format_extension" }
```

**B. Definir o Resolver como padrão global (`config/packages/liip_imagine.yaml`):**
```yaml
liip_imagine:
    driver: "gd"
    cache: format_extension # Aplica o resolver recém-registrado
    twig:
        mode: legacy # Mantém a renderização direta da URL, agora com a extensão correta
```
*Efeito:* O Twig passa a gerar `imagem.webp` de forma nativa no HTML. A cadeia de redirecionamentos 302 desaparece e o servidor web (Nginx/Apache) entrega a imagem estática com Status 200 OK.

---

## 3. Compressão Extrema e Formatos de Próxima Geração

Banners de alta resolução devem ser compactados agressivamente e obrigatoriamente servidos em WebP/AVIF. 

**Configuração Ideal para Banners Heróis (`liip_imagine.yaml`):**
```yaml
liip_imagine:
    filter_sets:
        banner_hero_desktop:
            quality: 30 # Compressão extremamente agressiva (Avaliar fidelidade visual)
            format: webp
            filters:
                thumbnail: { size: [1280, 500], mode: outbound }
        banner_hero_mobile:
            quality: 20 # Mobile aceita compressão ainda maior devido ao tamanho da tela
            format: webp
            filters:
                thumbnail: { size: [640, 500], mode: outbound }
```

---

## 4. Otimização de Conteúdo Dinâmico (WYSIWYG HTML)

Textos e artigos cadastrados via painéis CMS (CKEditor/TinyMCE) frequentemente contêm imagens puras (`<img src="legado.jpg">`) que escapam do controle dos filtros Twig e afundam a performance de páginas internas.

**Solução Aplicada:**
Criação de uma **Twig Extension Customizada (`ImageOptimizerExtension.php`)** que varre blocos de código HTML via `preg_replace_callback`, localiza atributos `src="..."`, e submete essas imagens originais ao `CacheManager` do LiipImagine. O resultado é a injeção em tempo real de imagens em WebP otimizadas e redimensionadas dentro de qualquer artigo dinâmico.

---

## 5. Acessibilidade de Fontes (Evitando Textos Invisíveis)

O Lighthouse acusa a falha *"Ensure text remains visible during webfont load"*, o que penaliza o UX e o Speed Index.

**Solução Padrão:**
Sempre aplique a propriedade CSS `font-display: swap`. 
Para bibliotecas de ícones providas via CDN (como Bootstrap Icons ou FontAwesome), onde você não controla a declaração, faça a sobrescrita `@font-face` no seu arquivo CSS principal:

```css
@font-face {
  font-family: "bootstrap-icons";
  src: url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2") format("woff2");
  font-display: swap; /* Força o navegador a mostrar uma fonte de fallback enquanto a principal carrega */
}
```

---

## 6. Acessibilidade Visual e Contraste (WCAG AA)

Para pontuar `100/100` em Acessibilidade, as cores de botões e links sobrepostas a planos de fundo devem respeitar estritamente a taxa de contraste do padrão WCAG AA.
- O PageSpeed penaliza "Cores de primeiro e segundo plano não têm uma taxa de contraste suficiente".
- Utilize ferramentas como o Color Contrast Analyzer ou o inspecionador nativo do Chrome DevTools para escurecer fundos ou clarear textos até que o contraste atinja o mínimo seguro.

---

## 📋 Checklist Final para Rodar Testes de Avaliação

**1. Ambiente Esterilizado:** 
Nunca rode o Lighthouse (PageSpeed Insights) em uma janela comum de navegador com extensões instaladas. A injeção de scripts (Grammarly, Tag Assistant, bloqueadores de anúncio) e dados armazenados localmente (`IndexedDB`) contaminam os resultados com falsos-positivos ou avisos extras. **Use sempre janela anônima.**

**2. Aquecimento do Cache do Servidor (Warmup):**
Após subir uma atualização para produção e limpar os caches (`php bin/console cache:clear`), o sistema está "frio". 
- *Se você rodar a auditoria imediatamente, o servidor demorará centenas de milissegundos a mais para compilar scripts e renderizar as imagens WebP na hora, gerando lentidão irreal.*
- **Procedimento Obrigatório:** Abra a URL recém atualizada em seu navegador normal. Dê um refresh. Garanta que todas as imagens abriram na tela. Somente após esse "aquecimento", copie a URL e jogue no site do PageSpeed Insights.
