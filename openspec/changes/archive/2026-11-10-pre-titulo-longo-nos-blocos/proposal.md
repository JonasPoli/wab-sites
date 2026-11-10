# Proposta: pre-titulo-longo-nos-blocos

## Why

Na página https://ajope.wab.com.br/p/quem-somos, o pré-título do bloco de imagem e texto é uma frase longa: "O AJoPE é o portal de notícias da globo e líder de audiência no jornalismo digital no Brasil. Conheça mais sobre a história do g1."

Os blocos do tema cetec (que o tema `noticias` também usa) mostram o pré-título como uma pílula feita para rótulos curtos: `px-3 py-1`, texto de 12 px em caixa alta, `rounded-full`. Com a frase longa, ela quebra em três linhas apertadas, sem respiro, como o Jonas mostrou em 10/11/2026.

Nos 8 sites de cliente, o pré-título mais longo das home tem 25 caracteres (conferido no HTML público em 10/11/2026).

## What Changes

- **Pré-título com mais de 40 caracteres:** passa a ser um destaque legível, com mais espaço interno (`px-5 py-3`), cantos menos redondos (`rounded-xl`), texto de 14 px (16 px a partir de 768 px), sem caixa alta, com entrelinha folgada e largura máxima de leitura (`max-w-3xl`). As cores continuam as do tema; no modo escuro, o texto fica branco, porque a cor primária do escuro sobre o fundo amarelado dava 3,41:1.
- **Pré-título com até 40 caracteres:** continua com a pílula de hoje, com a mesma marcação.
- Vale para os 14 tipos de bloco do cetec que mostram pré-título, nos temas cetec e noticias.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `construtor-de-paginas`: requisito novo sobre a aparência do pré-título dos blocos.

## Impact

- **Templates:** partial novo `themes/cetec/blocks/_pre_title.html.twig`, usado pelos 14 blocos do cetec que mostram `block.preTitle`. O tema `noticias` usa esses blocos, e os temas moderno e wab não mudam. Os rótulos fixos da home e da página do cetec continuam como estão.
- **CSS:** as classes novas do Tailwind entram no `./build.sh`.
- **Sites de cliente:** nenhum muda hoje, porque nenhum tem pré-título com mais de 40 caracteres na home.
