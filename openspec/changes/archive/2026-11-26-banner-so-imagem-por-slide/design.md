# Design

## Context

- A mudança `banner-so-imagem` (arquivada em 26/11/2026, publicada no commit `0af1f53`) guarda a opção no bloco (`config.imageOnly`). Cada `themes/<tema>/blocks/_banner.html.twig` (cetec, também usado pelo noticias; moderno; wab; atelie) desvia logo no topo para `themes/_comum/_banner_so_imagem.html.twig` quando ela vale `'1'`; senão, desenha o banner com texto do tema.
- O laço do carrossel com texto tem a mesma forma nos quatro temas: `{% for b in banners %}` e `<div class="swiper-slide" …>`; dentro, imagem de fundo (`<img>` com miniatura no cetec e no atelie, `background-image` no moderno e no wab), camadas e o conteúdo; a altura vem do `<style>` do bloco (`min-height` de 550/750 px ou `100vh`).
- `AtivosExtension::filtrosDoLcp()` e `TextoDeResumo::textosDoBloco()` leem `config.imageOnly` direto.
- O formulário (`admin/block/_form_banner.html.twig`) tem um interruptor no topo e marca `#banners-wrap.so-imagem`; o script põe `inert` nos `.campos-de-texto` de todos os cartões.

## Goals / Non-Goals

**Goals:**
- Escolha por slide, sem mudar uma linha do HTML dos banners sem slide "só a imagem" nem do banner com todos os slides "só a imagem".
- Uma regra só, em PHP, para "este slide é só a imagem" e "todos os slides são", usada pelos templates, pela pré-carga do LCP e pelo resumo de SEO.

**Non-Goals:**
- Altura por slide ou imagem de celular.
- Migração de dados: blocos gravados com a chave do bloco continuam valendo pela regra de compatibilidade.

## Decisions

### D1. Chaves e regra
- `banners[i].imageOnly`: `'1'`/`'0'` por slide, no molde de `active`.
- Regra em `App\Service\SlidesDoBanner` (métodos estáticos):
  - `soImagem(slide, config)`: vale `slide.imageOnly`; sem a chave no slide, vale `config.imageOnly` (bloco gravado antes desta mudança); sem nenhuma, `false`.
  - `todosSoImagem(config)`: há ao menos um slide ativo e todos os ativos são "só a imagem"; sem lista de slides (banner antigo de um slide só), vale `config.imageOnly`.
- `populateBlock()` grava `imageOnly` em cada slide e tira `config.imageOnly`: depois de salvo pelo painel, a escolha está sempre no slide.
- Alternativa descartada: manter o interruptor do bloco como "todos" e acrescentar o do slide. Dois controles para a mesma coisa confundem, e "todos" é só ligar em cada slide.

### D2. Funções Twig
- Extensão nova `App\Twig\BannerExtension` com `slide_so_imagem(slide, config)` e `banner_so_imagem(config)`, que chamam `SlidesDoBanner`.
- O desvio do topo dos quatro parciais troca `(block.config.imageOnly ?? '0') == '1'` por `banner_so_imagem(block.config ?? {})`. Banner com todos os slides "só a imagem" continua no parcial comum, idêntico ao de hoje.

### D3. Slide "só a imagem" no carrossel com texto
- Parcial novo `themes/_comum/_slide_so_imagem.html.twig`, incluído logo depois da abertura de `<div class="swiper-slide" …>` no laço dos quatro temas: `{% if slide_so_imagem(b, block.config ?? {}) %}{% include … with {primeiro: loop.first} %}{% else %}` e `{% endif %}` antes do `</div>` do slide.
- As duas linhas novas ficam na coluna zero, sem indentação: o Twig come a quebra de linha depois da tag e não sobra espaço, então o HTML dos slides com texto sai byte a byte igual ao de hoje.
- O slide desenha uma `<img>` com `position:absolute; inset:0; width:100%; height:100%; object-fit:cover`, sem classe `banner-bg` (o zoom do `:hover` do tema é nessa classe), sem camada e sem conteúdo. A área tem a altura dos slides com texto (o `min-height` do `<style>` do bloco), então a arte é recortada se a proporção for outra.
- Miniaturas `banner_mobile`/`banner_desktop`, as mesmas do carrossel com texto: a área é a mesma, e a pré-carga do LCP continua pedindo os mesmos arquivos (o `filtrosDoLcp()` devolve esses filtros sempre que nem todos os slides são "só a imagem"). Primeiro slide com `src` e `fetchpriority="high"`; os outros com `data-adiada`, como hoje.
- `alt` com a descrição; sem descrição, `alt=""` e `aria-hidden="true"`.
- Achado na conferência: no moderno e no wab a altura do slide vem do conteúdo (`slideStyles`, com `min-height:350px` ou `100vh`), e o `height:100%` que o Swiper põe nos slides não resolve; o slide só com a imagem absoluta ficava com altura zero. O parcial acrescenta um espaçador vazio (`aria-hidden`) com o `slideStyles` do tema e, para este carrossel, `.swiper-wrapper{align-items:stretch}` e `.swiper-slide{height:auto}`, que esticam todos os slides até o mais alto. Isso só sai em banner com slide "só a imagem"; os banners sem ele não mudam.

### D4. Formulário
- Sai o interruptor do topo; no lugar, uma dica fixa: "Cada slide pode mostrar só a imagem. Com todos assim, a arte aparece inteira; misturando slides com texto, vale a altura dos slides com texto e a arte preenche a área, podendo ser recortada nas bordas."
- Cada cartão ganha, logo abaixo do cabeçalho, `banners[i][imageOnly]` (oculto `0` + `sl-switch` `1`, classe `chave-so-imagem`), ligado quando `slide.imageOnly ?? block.config.imageOnly` é `'1'`. O cartão recebe a classe `so-imagem`.
- O CSS passa de `#banners-wrap.so-imagem …` para `.slide-card.so-imagem …`. O script escuta `sl-change` em `#banners-wrap` (o evento sobe da chave), ajusta só aquele cartão (`inert`, `required`, classe) e dispara `input` no formulário para a prévia. Slide novo nasce com a opção desligada.

### D5. LCP e resumo
- `filtrosDoLcp()`: `banner_inteiro*` quando `todosSoImagem(config)`; senão, os filtros do banner com texto.
- `TextoDeResumo::textosDoBloco()`: no banner, o primeiro slide ativo com imagem decide: "só a imagem" dá a descrição dele; com texto, o texto e o título do bloco, como antes.

### D6. Isolamento por tenant
Nenhuma consulta nova; o bloco continua lido e gravado pela seção da página ou categoria do tenant do domínio.

## Risks / Trade-offs

- [No banner misto, a arte é recortada para caber na altura dos slides com texto] → aviso fixo no formulário; para a arte inteira, todos os slides "só a imagem".
- [No moderno e no wab, o slide "só a imagem" passa a usar `<img>` com miniatura, enquanto os slides com texto desses temas usam `background-image` com o arquivo original] → o slide "só a imagem" fica mais leve; os slides com texto não mudam (fora do escopo).
- [Bloco antigo com `config.imageOnly` e slides sem a chave] → a regra de compatibilidade (D1) trata todos como "só a imagem" até o próximo salvamento, quando a escolha passa para os slides.

## Migration Plan

1. Deploy (escrita, autorizado): backup, `git pull` e `./build.sh` (template e PHP; filtros de miniatura sem mudança), sem migration.
2. Nada muda sozinho: os blocos atuais não têm slide "só a imagem" (o bloco 147 da Transmai está com a opção desligada).
3. Rollback: `git revert` e `./build.sh`. Slides gravados com `imageOnly` voltam a sair com texto (a opção antiga lia só a chave do bloco).
