# Design

## Context

Estado atual (motivação em `proposal.md`):

- `themes/_topbar.html.twig` é incluído no topo do `_header.html.twig` de cetec, moderno, wab e atelie (o noticias não inclui). Ele lê `navigationSettings.topBarEnabled`, `topBarLeft` e `topBarRight` (listas de nomes: `social`, `phone`, `email`, `address`, `hours`) e desenha cada nome por uma macro, com ícones fixos (`ux_icon('fa6-solid:phone')` etc.). Fundo `var(--color-primary)`, texto `var(--topbar-text)`, altura `var(--topbar-height)` (40 px ligada, 0 desligada), todos de `TenantExtension::getCssVars()`, que já troca as cores no modo escuro.
- A barra é `position: relative` no fluxo da página. O cabeçalho é `sticky; top: 0` no cetec, wab, atelie e noticias: a barra já sai da tela e o cabeçalho encosta no topo. No moderno o cabeçalho é `fixed; top: var(--topbar-height)` e o `body` tem `padding-top: calc(70px + var(--topbar-height))`: ao rolar, o cabeçalho fica parado a 40 px do topo e o conteúdo aparece no vão.
- `navigationSettings` é remontado chave por chave ao salvar, em `AdminContentController::settings()` e em `SuperAdminController::populateTenantFromRequest()` (este preserva `customMenuItems`, `headerCta*` e `headerTagline`).
- Em produção, só o tenant 11 (Pichoneri, atelie) tem a barra ligada: `["hours"]` à esquerda e `["social", "phone"]` à direita. O tenant 1 tem itens gravados com a barra desligada.
- O seletor de ícones (`openIconPicker(inputId, previewId)`) está em `admin/_seletor_de_icones.html.twig`, incluído no `wab_base` do painel e na edição do tenant do `/superadmin` (change botao-flutuante-do-site).

## Goals / Non-Goals

**Goals:**
- Os dois exemplos do pedido (kayoacorp.com.br e cetecararaquara.com.br) podem ser montados só pelo painel.
- Nenhum site muda de aparência no deploy (a Pichoneri sai igual).

**Non-Goals:**
- Barra fixa no topo (opção de "grudar"), mais de uma linha, menus suspensos na barra, cor por item.
- Esconder a barra por página.

## Decisions

### D1. Formato gravado

Em `navigationSettings`:

| Chave | Valor |
|---|---|
| `topBarEnabled` | bool (como hoje) |
| `topBarBg` | `#rrggbb` ou `''` |
| `topBarLeft`, `topBarRight` | lista de `{type, text, icon, url, newTab}` |

`type` ∈ `phone`, `whatsapp`, `email`, `address`, `hours`, `social`, `custom`. `text` vale para phone/whatsapp/email/address/custom; `icon`, `url` e `newTab` só para custom. Itens no formato antigo (string) são lidos como `{type: <string>}`.

*Alternativa descartada:* colunas novas no `tenant`. A barra já mora no JSON do menu, que a exportação e a importação levam inteiro; coluna nova pediria migration e mudança no exportador sem ganho.

### D2. Um serviço só: `App\Service\BarraSuperior`

- `normalizarLado(mixed $itens): array`: aceita strings antigas e arrays; descarta tipo desconhecido e custom sem ícone e sem texto; corta `text` em 80, `icon` só `[a-z0-9 -]` até 80 (como o botão flutuante), `url` com `trim`, até 500, e o mesmo complemento de `https://` dos itens de menu; no máximo 10 itens. Usado ao salvar nas duas telas e ao desenhar (para o formato antigo e para dado gravado à mão).
- `normalizarCor(?string)`: `#rrggbb` em minúsculas ou `''`.
- `doSite(?Tenant): ?array`: `null` com a barra desligada; senão `['fundo' => …, 'esquerda' => [...], 'direita' => [...]]`, com cada item **resolvido**: `tipo`, `texto`, `url`, `novaAba`, `icone` (classe do painel, só custom), `redes` (só social: lista de `{rede, url}`), `titulo` (texto completo para `title`/rótulo acessível). Itens sem dado somem aqui (D-spec "Dado vazio").
  - phone: `tel:` + só os dígitos e o `+`.
  - whatsapp: link do site; texto padrão = número formatado dos dígitos do link (tira o 55 de 12–13 dígitos; `(DD) NNNNN-NNNN` ou `(DD) NNNN-NNNN`; se não reconhecer, "WhatsApp").
  - address: `https://www.google.com/maps/search/?api=1&query=` + endereço codificado, nova aba.
  - hours: o mesmo texto de hoje (`dias horas – dias horas`), sem link.
  - social: WhatsApp, Instagram, Facebook, YouTube, LinkedIn, na ordem de hoje, só os preenchidos; nenhum preenchido = item omitido.
- Função Twig `barra_superior()` (extensão nova, tenant do `TenantContext`), resultado guardado por requisição (`ResetInterface`).

### D3. Desenho: `themes/_topbar.html.twig` reescrito

Mesmo invólucro (`.topbar-wrapper`, container, dois lados `flex`), com os ícones fixos continuando como `ux_icon('<nome literal>')` no template (o `ux:icons:lock` acha os nomes) e o do personalizado por `icone_do_painel`. As classes de texto de cada tipo continuam as de hoje (`hidden sm:inline` no telefone, `md` no e-mail e no horário, `lg` no endereço) e `hidden md:inline` nos novos (WhatsApp e personalizado com ícone); item sem ícone mostra sempre o texto. O lado direito ganha `min-w-0` e o texto `truncate` para nunca empurrar a largura; a barra mantém `overflow: hidden` e a altura de 40 px.

Para a Pichoneri sair igual, o HTML dos cinco tipos antigos é o mesmo de hoje; a comparação antes/depois do `atelie.localhost` (fixture com os mesmos itens) é a prova.

**Cor:** sem `topBarBg`, fica `var(--color-primary)` / `var(--topbar-text)` (como hoje). Com `topBarBg`, o parcial escreve `background` e `color` inline, com a cor do texto pelo `is_dark_color()` e a borda clara ou escura do mesmo jeito que o `TenantExtension` faz.

**Temas:** cetec, moderno, wab e atelie já incluem o parcial; o noticias passa a incluí-lo no topo do `_header.html.twig` (o `_header` já não é desenhado na prévia). O noticias carrega o `app.css` com as utilidades do Tailwind usadas pelo parcial; confirmar na conferência visual.

### D4. Moderno sem vão

O `top` do `.moderno-header` e do `.mh-nav` (menu do celular) passa a usar `var(--mh-topo, var(--topbar-height, 0px))`. Um script curto no `_header` do moderno, só quando a barra está ligada, atualiza `--mh-topo` no `<html>` com `max(0, alturaDaBarra − scrollY)` num `requestAnimationFrame` disparado por `scroll` (passivo). A transição de `top` sai (com ela, o cabeçalho "atrasa" ao rolar). O `padding-top` do `body` continua o mesmo; como ele empurrava a barra (que está no fluxo) para baixo do cabeçalho fixo, a barra ganha, só no moderno, `margin-top: calc(-70px - var(--topbar-height))` e `margin-bottom: 70px`, ficando em 0–40 px com o conteúdo começando onde começava.

*Alternativa descartada:* trocar o cabeçalho do moderno por `sticky`. Muda o empilhamento com o fundo de vidro e a seção de topo de todas as páginas do tema, um risco maior do que o defeito.

### D5. Editor: `admin/_editor_da_barra_superior.html.twig`

Parcial incluído no `admin/settings.html.twig` e no `superadmin/tenant/edit.html.twig`, no lugar das caixas atuais:
- caixa "Ativar barra superior" (`topBarEnabled`, mesmo nome de hoje) e cor (`topBarBg`: campo de cor + "usar a cor primária" que zera);
- duas colunas, "Lado esquerdo" e "Lado direito"; cada item é um cartão com o tipo, os campos do tipo (D1) e os botões ↑ ↓ ⇄ (outro lado) ✕; embaixo, um `<select>` "Acrescentar…" com os sete tipos;
- o estado vive num objeto JS `{left: [], right: []}` e é escrito a cada mudança num `input type=hidden name="topBarJson"`. Assim a ordem e os itens não dependem de nomes de campo, e o servidor lê um JSON só.
- o ícone do personalizado usa `openIconPicker` com ids únicos por item; como o seletor grava o valor sem disparar evento, o editor relê o campo ao fechar o seletor (clique na grade), como no botão flutuante.

### D6. Gravação nas duas telas

Nos dois controladores, quando o POST traz `topBarJson`: `topBarLeft`/`topBarRight` = `BarraSuperior::normalizarLado()` de cada lado e `topBarBg` = `normalizarCor()`. Sem `topBarJson` (outro formulário, cadastro de tenant novo), os três valores gravados continuam. O `/superadmin` passa a preservar também `topBarBg`. Isolamento: a escrita é só no tenant da tela (o do domínio no `/admin`, o editado no `/superadmin`); nenhuma página, seção ou bloco é lido.

### D7. Ícones

`SincronizarIconesCommand::nomesEmUso()` lê `tenant.navigation_settings` e inclui o `icon` dos itens custom dos dois lados.

## Risks / Trade-offs

- [Itens demais para a largura] → `overflow: hidden`, textos com `truncate` e, no celular, só ícones; o limite de 10 por lado evita o pior. Fica com o admin escolher bem.
- [Ícone novo escolhido sem estar em `assets/icons/`] → o UX Icons busca no Iconify sob demanda; rodar `app:icones:sincronizar` e commitar quando for usado.
- [Script de rolagem do moderno] → só roda com a barra ligada; sem JavaScript, fica como hoje.
- [Formato misto gravado] → o normalizador aceita os dois em qualquer leitura; o formato novo só é gravado quando alguém salva uma das telas.

## Migration Plan

1. Backup do banco de produção (escrita de arquivo no servidor).
2. `git pull` e `./build.sh` (templates e CSS); sem migration.
3. Conferir: HTTP 200 dos domínios; a barra da Pichoneri igual à de antes; nenhum outro site com barra.

Volta: `git checkout` do commit anterior + `./build.sh`. O código antigo compara strings e não desenha nenhum item gravado no formato novo (a barra sai vazia), então a volta sem perda só vale antes de alguém salvar a barra nova.
