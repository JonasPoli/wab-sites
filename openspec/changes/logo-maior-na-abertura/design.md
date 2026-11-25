# Design

## Context

Logo do cabeçalho hoje, por tema (todos com `logo-light`/`logo-dark` trocados pelo modo, menos atelie e noticias):

| Tema | Logo | Altura normal | Cabeçalho | Posição |
|---|---|---|---|---|
| cetec | `img.logo-light/.logo-dark`, classes `h-12 md:h-14`, miniatura `logo_site` | 48 px (< 768) / 56 px | `.container` com `h-[70px]` | `sticky` |
| wab | `.header-logo` (52 px, `overflow:hidden`) com `img` `max-height:52px` inline, arquivo original | 52 px | `.header-inner` 72 px | `sticky` |
| moderno | `img` `max-height:40px` inline, arquivo original | 40 px | `.mh-inner` 70 px | `fixed`; `body` com `padding-top: calc(70px + barra)`; a barra superior tem margens calculadas com os mesmos 70 px |
| atelie | `.at-logo img` 58 px, `max-width:170px`, miniatura `logo_site` | 58 px | `.at-nav` `min-height:91px` | `sticky` |
| noticias | `.nt-logo img` 44 px (40 no fundo branco, 36 na página de notícia), miniatura `logo_site` | 44 px | `.nt-header__inner` 64 px; `.nt-search` `sticky; top:64px` | `sticky` |

A miniatura `logo_site` é `thumbnail 440×120 inset`: acima de 60 px de altura numa tela 2×, ela já não tem pixels suficientes. O rodapé usa outros seletores.

## Goals / Non-Goals

**Goals:**
- Mesmo comportamento nos cinco temas, com um só mecanismo.
- Nenhuma mudança no HTML dos sites com a opção desligada.

**Non-Goals:**
- Escolha da altura normal (rolada) do logo, do ponto de início da redução ou de animação diferente.
- Logo maior só na home (vale em todas as páginas).
- Opção no `/admin/settings` do site (só no `/superadmin`, como pedido).

## Decisions

### D1. Dados: coluna JSON `tenant.header_logo`

`{ "large": bool, "height": int }`, nula = desligada. `Tenant::getHeaderLogo()` devolve sempre `{large, height}` com `height` entre 60 e 200 (padrão 110). Entra em `__serialize`/`__unserialize`, no `TenantExporter` e no `TenantImporter`.

*Alternativa descartada:* guardar em `navigationSettings`. O `/admin/settings` remonta esse JSON chave por chave e apagaria a escolha feita no `/superadmin`.

### D2. Um parcial com uma variável CSS: `themes/_comum/_logo_grande.html.twig`

Incluído no fim do `_header.html.twig` de cada tema com `{tema, normal, normalCelular}`; não desenha nada com a opção desligada ou com altura ≤ normal. Ele emite:

- um `<style>` com, para o tema, a altura do logo, a do cabeçalho e os limites de largura do logo (que cortariam o logo grande) escritos como `calc(<normal> + var(--wab-logo-extra, 0px))`, com `!important` onde o tema usa estilo inline (wab e moderno) ou classes do Tailwind (cetec), e `transition: none` no logo (a transição de 300 ms do cetec faria o logo "atrasar" em relação à rolagem);
- um script que calcula `D = altura − normal` (metade abaixo de 768 px, `matchMedia`) e escreve em `<html>` `--wab-logo-extra = max(0, D − scrollY)` px, num `requestAnimationFrame` disparado por `scroll` (passivo) e por mudança de largura.

O valor inicial da variável no `<style>` é `D` (versão desktop), para o logo já sair grande antes do script rodar e sem JavaScript.

Por tema:
- **cetec:** `.cetec-header .container { height: calc(70px + extra) }`; `.cetec-header img.logo-light, .cetec-header img.logo-dark { height: calc(56px + extra); max-width: none }` (abaixo de 768 px, `48px + extra`).
- **wab:** `.header-inner { height: calc(72px + extra) }`; `.header-logo { height: calc(52px + extra) }`; `.header-logo img { max-height: calc(52px + extra) !important; max-width: none !important }`.
- **moderno:** `.mh-inner { height: calc(70px + extra) }`; `img` do `.mh-logo` com `max-height: calc(40px + extra) !important`; e os três lugares que usam os 70 px do cabeçalho (`body` `padding-top`, margens da `.topbar-wrapper`, `top` do `.mh-nav` no celular) passam a somar `var(--wab-logo-extra, 0px)` no próprio `_header` do moderno (sem a opção, a variável não existe e vale 0: o CSS sai igual).
- **atelie:** `.at-nav { min-height: calc(91px + extra) }`; `.at-logo img { height: calc(58px + extra); max-width: none }`.
- **noticias:** `.nt-header__inner { height: calc(64px + extra) }`; `.nt-header .nt-logo img { height: calc(44px + extra) }` (40 no fundo branco, 36 na página de notícia); `.nt-search { top: calc(64px + extra) }`.

Com cabeçalho `sticky` (cetec, wab, atelie, noticias), encolher o cabeçalho durante a rolagem faz o conteúdo subir um pouco mais rápido nos primeiros `D` px. Não há salto nem vai e volta, porque a altura depende só da rolagem e diminui sempre que ela aumenta. O parcial desliga o *scroll anchoring* (`html { overflow-anchor: none }`): sem isso, o navegador devolve à rolagem os pixels que o cabeçalho perdeu e o logo não diminui em rolagens curtas (achado na conferência: rolar 30 px voltava a 0). Esse efeito é o mesmo das referências.

*Alternativa descartada:* trocar uma classe ("rolado") com transição CSS a partir de N px. Num cabeçalho `sticky`, encolher de uma vez muda o fluxo da página e faz o conteúdo pular, o que pode ligar e desligar a classe perto do limite.

### D3. Logo nítido: filtro `logo_site_grande`

Filtro novo `thumbnail 1200×400 inset` (WebP, qualidade 85). Com a opção ligada, cetec, atelie e noticias usam `thumb_attrs('logo_site_grande')` no logo do cabeçalho no lugar de `logo_site`; wab e moderno já servem o arquivo original. `AquecerMiniaturasCommand` passa a aquecer `logo_site_grande` para `uploads/tenant/logo` e `uploads/tenant/dark_logo`. Os atributos `width`/`height` do `thumb_attrs` são da miniatura e não mandam no tamanho mostrado, porque o CSS do tema define a altura.

### D4. Tela do `/superadmin`

Logo abaixo dos campos de logo: caixa "Logo maior na abertura da página" (`headerLogoLarge`), controle deslizante com número (`headerLogoHeight`, 60–200, passo 2) e prévia: o logo atual (ou o escuro, sobre fundo escuro) em altura real ao lado de uma régua com a altura normal do tema escolhido. Um `headerLogoForm=1` oculto marca que o grupo veio no POST: sem ele, nada muda (cadastro de tenant novo). Gravação: `large` = caixa marcada; `height` = inteiro trazido para 60–200.

## Risks / Trade-offs

- [Logo muito largo] → com a largura livre, um logo horizontal de 200 px de altura pode empurrar o menu. A prévia mostra a largura real; a altura máxima de 200 px e a metade no celular limitam o problema.
- [Rolagem com script] → um `requestAnimationFrame` por quadro com rolagem, só escrevendo uma variável; sem JavaScript, o logo fica grande (cabeçalho maior), sem quebrar nada.
- [Miniatura nova] → o primeiro acesso depois do deploy gera a `logo_site_grande` (o `build.sh` limpa e reaquece as miniaturas).

## Migration Plan

1. Backup do banco de produção.
2. `git pull`, `./build.sh`, `doctrine:migrations:migrate`.
3. Conferir: HTTP 200 dos domínios; nenhum site mudou (opção desligada em todos); a tela do tenant 13 abre com o grupo novo.

Volta: commit anterior + `./build.sh`; a coluna pode ficar.
