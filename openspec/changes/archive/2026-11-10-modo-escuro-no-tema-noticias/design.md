# Design: modo-escuro-no-tema-noticias

## Context

Motivação em `proposal.md`. Requisitos em `specs/navegacao-e-aparencia/spec.md`.

Estado atual:

- `themes/noticias/base.html.twig` fixa `data-theme="light"` no `<html>` e declara em `:root` os tokens que os blocos do cetec usam (`--bg`, `--bg-alt`, `--bg-card`, `--text`, `--text-muted`, `--border`) e os do portal (`--nt-page-bg`, `--nt-ink`, `--nt-gray`, `--nt-light`, `--nt-line`, `--nt-on-primary`). Grande parte do CSS do portal, porém, usa cores fixas: em `_news_styles.html.twig` (`#fff` nos cartões, `#333`, `#3f3f3f`, `#111`, `#555`, `#e6e6e6`, `#ececec`), no menu lateral (`background: #fff`), nos avisos, em `journalist_signup.html.twig` (6 cores) e nas páginas de membro (`team_member_classic` com 18 cores e `team_member_modern` com 11).
- `tenant_css_vars()` já gera o bloco `[data-theme="dark"], .dark, ...` que troca `--color-primary`, `--color-secondary` e `--topbar-text` pelas versões do modo escuro, e os fundos `auto-bg-a` e `auto-bg-b` pelos escuros. O portal usa `--color-primary` tanto no fundo do cabeçalho quanto na cor dos títulos, e `--nt-on-primary` vem de `--topbar-text`.
- Os temas cetec e moderno aplicam o modo com um script no `<head>` antes da pintura (`localStorage['wab-theme']`, `data-theme` e classe `dark` no `<html>`), e a função `cycleTheme()` fica no fim do `<body>`. O Tailwind está com `darkMode: 'selector'`, ou seja, os blocos do cetec reagem à classe `dark`.
- AJoPE em produção (HTML público de 10/11/2026): primária `#00275c`, secundária `#feb101`, cores do modo escuro no padrão (`#3b82f6`, `#fbbf24`, `#0d0f1a`, `#131625`), sem logo escuro visível no HTML.

## Goals / Non-Goals

**Goals:**
- Modo escuro completo e legível em todas as páginas públicas do tema `noticias`, com as cores escuras que o site já tem no painel.
- Modo claro igual ao de hoje.
- Uma única fonte de cores no tema (variáveis), para que telas novas do portal já nasçam com os dois modos.

**Non-Goals:**
- Mudar os temas `cetec`, `moderno` e `wab` ou o script deles.
- Configuração por site do modo padrão ou opção para esconder o seletor (o padrão é Claro para todos os portais).
- Modo escuro na área do jornalista (`/jornalista`, layout próprio) e no `/admin`.
- Mudar a função `tenant_css_vars()` ou o `/admin/settings`.

## Decisions

### 1. Mesmo mecanismo dos outros temas, com código próprio do tema
O modo continua sendo `data-theme` mais a classe `dark` no `<html>`, com a escolha em `localStorage['wab-theme']` (`light`, `dark` ou `auto`) e o padrão `light`, como no cetec. Um partial novo, `themes/noticias/_theme_mode.html.twig`, traz o script curto do `<head>`, que roda antes do CSS e não depende de nada, e o script do botão, que roda no fim da página com `matchMedia('(prefers-color-scheme: dark)')` para o Automático. A chave é a mesma dos outros temas. Como o `localStorage` é separado por domínio, cada portal guarda a sua escolha.

Alternativa: extrair um partial comum para os quatro temas. Fica para depois: mexeria no cetec, que todos os sites reais usam, sem ganho para este pedido.

### 2. Barra da marca separada da cor de destaque
Hoje `--color-primary` faz dois papéis, fundo do cabeçalho e cor dos títulos, e no modo escuro o `tenant_css_vars()` a troca pela primária do modo escuro (`#3b82f6` por padrão). O tema passa a declarar tokens próprios, calculados no `base.html.twig` a partir do tenant e fora do bloco escuro:
- `--nt-bar`: a primária do modo claro, nos dois modos (fundo do cabeçalho, do rodapé e do botão "Seja um autor");
- `--nt-on-bar`: `#ffffff` se `is_dark_color(primaryColor)`, senão `#1f2937`;
- `--nt-accent`: no claro, `var(--color-primary)` (como hoje); no escuro, a primária do modo escuro, ou `--nt-ink` quando ela não passa no contraste (decisão 3).

Os seletores do portal que hoje usam `var(--color-primary)` para texto passam a usar `--nt-accent`, e os de fundo da barra, `--nt-bar`. A regra do logo continua lendo `is_dark_color(primaryColor)` e por isso não muda.

Alternativa: usar a primária do modo escuro também na barra. Descartada: com o padrão `#3b82f6`, o cabeçalho da AJoPE ficaria azul-claro, fora da identidade, e a escolha do logo passaria a depender do modo, que só o navegador conhece.

### 3. Contraste calculado no servidor
`TenantExtension` ganha a função Twig `contrast_ratio(cor1, cor2)`, com a fórmula de luminância relativa do WCAG 2.x, numa classe pura `App\Twig\ColorContrast` (fácil de conferir com `php -r`). O `base.html.twig` usa a primária do modo escuro como `--nt-accent` escuro só se `contrast_ratio(primaryColorDark, bgColorDark1)` e `contrast_ratio(primaryColorDark, bgColorDark2)` forem ambos ≥ 4,5; senão, usa `--nt-ink` escuro (`#e5e7eb`). Valores conferidos: `#3b82f6` dá 5,19 sobre `#0d0f1a` e 4,88 sobre `#131625` (passa); `#00275c` dá 1,31 (não passa).

Alternativa: `is_dark_color()`, que já existe. Não serve: ela considera `#3b82f6` escura (limiar 170 numa média ponderada simples), e esse azul tem contraste suficiente.

### 4. Tokens do modo escuro
Em `[data-theme="dark"]`, no `base.html.twig` do tema:

| Token | Claro (hoje) | Escuro |
|---|---|---|
| `--nt-page-bg`, `--bg` | `#f7f7f7` / `#ffffff` | `bgColorDark1` (`#0d0f1a`) |
| `--nt-card`, `--bg-card`, `--bg-alt` (novo `--nt-card`) | `#ffffff` | `bgColorDark2` (`#131625`) |
| `--nt-ink`, `--text` | `#1f1f1f` | `#e5e7eb` |
| `--nt-gray` | `#555` | `#cbd5e1` |
| `--nt-light`, `--text-muted` | `#6b7280` | `#94a3b8` |
| `--nt-line`, `--border` | `#dcdcdc` / `#e5e7eb` | `rgba(255,255,255,.12)` |
| `--nt-field-border` (novo) | `#cfcfcf` | `rgba(255,255,255,.2)` |
| `--nt-img-ph` (novo, fundo de imagem carregando) | `#e5e7eb` | `#1e2236` |
| `color-scheme` | `light` | `dark` (barras de rolagem e campos nativos) |

Os contrastes do escuro passam de 4,5:1: `#e5e7eb` dá 15,4 sobre `#0d0f1a` e 14,5 sobre `#131625`; `#94a3b8` dá 7,4 e 7,0. Os tons intermediários fixos de hoje (`#333`, `#3f3f3f`, `#444`, `#111`, `#e6e6e6`, `#ececec`) são trocados pelo token mais próximo, com o valor do modo claro igual ao fixo de hoje, para o claro não mudar. Os avisos (`.flash-*`) e o erro do formulário ganham variantes escuras.

### 5. Botão no canto direito do cabeçalho
Em `.nt-header__right`: `<button type="button" class="nt-mode-btn">` com ícone do Bootstrap Icons (já carregado): `bi-sun` (Claro), `bi-moon-stars` (Escuro) e `bi-circle-half` (Automático), e o nome do modo num `<span>` escondido abaixo de 768 px. `aria-label` "Aparência: <modo>. Mudar para <próximo>" e `title` iguais, atualizados a cada clique. Estilo igual ao do `.nt-menu-btn` (fundo transparente, hover em branco translúcido, `color: inherit`), para servir em qualquer cor de barra. A largura da coluna da direita do grid já existe (`1fr`), então o logo continua centralizado.

### 6. Blocos do cetec
Os tokens do cetec (`--bg`, `--bg-alt`, `--bg-card`, `--text`, `--text-muted`, `--border`) ganham valores escuros no mesmo `[data-theme="dark"]`, e a classe `dark` no `<html>` liga as variantes `dark:` do Tailwind. Seções com fundo próprio seguem com `mp-section-dark` ou com a cor clara que têm; as automáticas já alternam `auto-bg-a` e `auto-bg-b` pelo `tenant_css_vars()`.

### 7. Escopo dos templates
Só a pasta `templates/themes/noticias/`: `base`, `_header`, `_news_styles`, `journalist_signup`, `team_member_classic`, `team_member_modern` e o partial novo `_theme_mode`. Nenhum template dos temas `cetec`, `moderno` ou `wab` muda. O `_block.html.twig` do noticias continua delegando aos blocos do cetec, sem mudança.

### 8. Isolamento e conteúdo
O change não lê nem grava conteúdo: lê só as cores e os logos do `currentTenant`, que o `TenantSubscriber` já resolveu pelo domínio. A escolha do visitante fica no navegador, nunca no servidor, então a página continua igual para todos e pode ir para cache.

## Risks / Trade-offs

- [Cor fixa esquecida num canto (texto escuro sobre fundo escuro)] → conferência página a página nos dois modos, mais uma busca por cores hexadecimais nos templates do tema, que só pode achar as dos tokens e as decorativas listadas.
- [Modo claro mudar sem querer na troca por tokens] → capturas antes e depois a 1280 px e a 390 px e comparação visual (requisito "Modo claro do tema noticias sem mudança").
- [Visitante com JavaScript desligado] → fica no Claro, que é o padrão, e o botão não faz nada; o portal continua legível.
- [Fotos muito claras no modo escuro] → aceito; as imagens não são filtradas.
- [Cor secundária no escuro] → os marcadores e a borda da citação usam `--color-secondary`, que o `tenant_css_vars()` já troca pela secundária do modo escuro (`#fbbf24`, contraste de 11,4 sobre `#0d0f1a`).

## Migration Plan

1. Local: implementar e conferir em `noticias.localhost` (fixtures do portal, com as páginas "Sobre" e "Todos os blocos") nos dois modos, e conferir em `127.0.0.1` (tema wab) e num tenant cetec local que nada mudou.
2. Produção (escrita, com aprovação do Jonas): anotar `git rev-parse HEAD`; `git pull` e `./build.sh` (mudaram templates e PHP); sem migration; HTTP 200 nos 8 domínios de cliente e no ajope.
3. Conferência no ajope nos dois modos (leitura).
4. Reversão: `git revert` do commit e `./build.sh`. Não há dado a desfazer; a escolha guardada no navegador de quem testou fica sem efeito.

## Open Questions

- Ajustar as cores do modo escuro da AJoPE no `/admin/settings` (por exemplo, um azul mais próximo do `#00275c` que ainda passe de 4,5:1, ou um logo escuro)? É configuração do site, pelo painel, e não muda o código; fica a critério do Jonas depois de ver o portal com o padrão `#3b82f6`.
