# Proposal

## Why

Sites como o da Transmai (https://www.transmai.com.br/revenda/: 49 revendas e assistências em 23 estados) precisam mostrar uma rede de representantes de um jeito que a pessoa ache o da sua região rápido. A referência aprovada pelo Jonas é o "Mapa de representantes" de https://bigdutchman.com.br/: mapa do Brasil à esquerda, o estado clicado fica na cor da marca e, ao lado, abre um cartão com os representantes daquele estado. Nenhum bloco atual faz isso.

## What Changes

- Tipo de bloco novo `representatives` ("Mapa de representantes"), oferecido no `/admin` de qualquer site:
  - cabeçalho com pré-título, título e **chamada** (o texto que aparece ao lado do mapa antes de escolher um estado, como "Clique no mapa e encontre o representante mais próximo da sua região");
  - mensagem para estados sem representante;
  - lista de registros, cada um com **estado (UF)**, **imagem** (logo, à esquerda da ficha) e **texto formatado em HTML** (negrito, itálico, sublinhado, listas, links e título só de nível `h3`).
- Mapa do Brasil em **SVG próprio** (27 estados, desenhados a partir da malha oficial do IBGE), **nas cores do tema**: estados com representante em cinza, o escolhido na cor primária do site, contorno no fundo da página; legível no modo escuro.
- Ao clicar num estado (ou escolher no seletor, que também serve ao teclado e ao celular), o cartão ao lado mostra o nome do estado e os registros dele, ou a mensagem para estados sem representante. Endereço com `#` abre o estado direto.
- Sem JavaScript: todos os cartões aparecem, um por estado, e clicar no estado leva ao cartão.
- Painel: lista de registros com filtro por estado, acrescentar, editar, mover e remover; logo enviado na hora (envio de `imagem-na-celula-da-sanfona`); editor de texto rico com título `h3`.
- Desenho em todos os temas (cetec, moderno, wab e noticias com o do cetec; atelie próprio), prévia com registros de exemplo, rótulo "Representantes" no menu do Modo Landing Page, exportação e importação dos logos.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: "Blocos por tipo" passa de 24 para 25 tipos; requisitos novos do mapa de representantes.
- `navegacao-e-aparencia`: rótulo do menu para seção cujo primeiro bloco é o mapa.

## Impact

- Código: `BlockType`, `AdminContentController::populateBlock()`, `PreviaDoBloco`, serviço novo `MapaDeRepresentantes`, `ArticleBodySanitizer` (aceita links `tel:`), `MidiaDoBloco::arquivosDoConfig()`.
- Templates: `admin/block/new.html.twig`, `admin/block/_form_representatives.html.twig`; `themes/_comum/_mapa_do_brasil.html.twig` (SVG) e `themes/_comum/_mapa_script.html.twig`; parciais `themes/cetec/blocks/_representatives.html.twig` e `themes/atelie/blocks/_representatives.html.twig`; despachantes e cabeçalhos dos temas; CSS `.wab-mapa` no `app.css` e `at-mapa` no atelie.
- Depende de `imagem-na-celula-da-sanfona` (envio de imagem e filtro `logo_bloco`). Sem migration.
