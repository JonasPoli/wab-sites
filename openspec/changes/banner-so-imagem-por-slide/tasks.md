# Tasks

> Leia antes: `design.md` desta mudança (D1 regra, D3 slide no carrossel com texto, D4 formulário) e, como base, `openspec/changes/archive/2026-11-26-banner-so-imagem/`. Conferência local nos sites `banner-<tema>.localhost:8011` (tenants 3 a 7) criados pelo `sites-de-banner.php` do scratchpad.

## 1. Regra por slide

- [x] 1.1 Antes de mexer, guardar o HTML atual das páginas `com-texto` e `so-imagem` dos cinco sites `banner-<tema>.localhost` e das homes de `127.0.0.1` e `localhost`, para a comparação da 4.2; verificar que os arquivos existem e têm os banners
- [x] 1.2 Criar `src/Service/SlidesDoBanner.php` (D1) e `tests/Service/SlidesDoBannerTest.php` cobrindo: chave do slide vence a do bloco, sem chave no slide vale a do bloco, sem nenhuma é falso; `todosSoImagem` com todos, com um só, sem slide ativo, com slide desativado de outro tipo e banner antigo sem lista; verificar com `vendor/bin/phpunit --filter SlidesDoBanner`
- [x] 1.3 Criar `src/Twig/BannerExtension.php` (D2) e usar `SlidesDoBanner` em `AtivosExtension::filtrosDoLcp()` e `TextoDeResumo::textosDoBloco()` (D5); acrescentar a `FiltrosDoLcpTest` e a `TextoDeResumoTest` os casos de slide por slide (todos "só a imagem", misto com o primeiro "só a imagem", misto com o primeiro com texto, bloco antigo com a chave do bloco); verificar com `vendor/bin/phpunit --filter 'FiltrosDoLcp|TextoDeResumo'`

## 2. Desenho no site

- [x] 2.1 Criar `templates/themes/_comum/_slide_so_imagem.html.twig` (D3) e, nos quatro `_banner.html.twig`, trocar o desvio do topo por `banner_so_imagem(...)` e acrescentar o desvio por slide no laço do carrossel, com as linhas novas na coluna zero; verificar com `bin/console lint:twig templates/themes` e `git diff` mostrando só essas linhas nos quatro parciais
- [x] 2.2 Acrescentar ao `BannerSoImagemTest`: banner misto (slide 1 com texto, slide 2 "só a imagem" com título guardado "Promoção" e descrição) nos cinco temas: 200, título e botão do slide 1 presentes, "Promoção" ausente, `alt` com a descrição, a `<img>` do slide 2 sem `banner-bg` e sem camada no slide, `data-adiada` na imagem do slide 2; banner com todos os slides "só a imagem" por slide sai no parcial comum (`banner-so-imagem`); banner com a chave antiga do bloco continua no parcial comum; primeiro slide "só a imagem" num misto com `fetchpriority="high"` e `imagesrcset` igual ao `srcset`; verificar com `vendor/bin/phpunit --filter BannerSoImagem`

## 3. Painel

- [x] 3.1 Em `populateBlock()`, gravar `imageOnly` por slide e tirar `config.imageOnly`; em `PreviaDoBloco::slides()`, levar `imageOnly`; ajustar no `BannerSoImagemTest` os POSTs da gravação e da prévia para o campo por slide e acrescentar: bloco com a chave antiga salvo pelo painel fica sem a chave do bloco e com `imageOnly` em cada slide; verificar com `vendor/bin/phpunit --filter BannerSoImagem`
- [x] 3.2 Em `_form_banner.html.twig`, conforme D4: tirar o interruptor do topo e pôr a dica, interruptor por cartão (gravados e `<template>`), CSS por cartão, script por cartão com `input` para a prévia; verificar no Chrome em `banner-cetec.localhost:8011/admin/block/<id>/edit`: ligar só no 2º slide (só ele fica inerte e mostra a descrição; os outros editáveis), a prévia mostra o slide 2 sem texto, salvar, reabrir e ver só o 2º ligado; num bloco gravado com a chave antiga, todos aparecem ligados
  - Conferido em 26/11/2026 no bloco 25 de `banner-cetec.localhost:8011`: ligado só no 2º slide, só o cartão 2 ficou inerte, sem `required` e com a descrição visível; o 1º seguiu editável. Na prévia, slide 1 com camada e título, slide 2 só com a imagem. Salvo e reaberto: interruptores `[off, on]` e a descrição gravada. O bloco 35 (página `so-imagem`, gravado com a chave antiga do bloco) abre com os três interruptores ligados e sem o interruptor do bloco.

## 4. Conferência integrada e documentação

- [x] 4.1 Acrescentar ao `sites-de-banner.php` uma página `misto` (carrossel com slide de texto, slide "só a imagem" com descrição e outro de texto) nos cinco sites e conferir no Chrome em 1280 px: slide "só a imagem" sem camada, texto, botão nem zoom, na mesma altura dos slides com texto; slides com texto como antes
  - Feito com `sites-de-banner.php misto` (slide com texto, slide "só a imagem" 1200×900 com título guardado e descrição, outro com texto; largura inteira). Com o carrossel parado no slide 2: cetec, noticias e atelie com os três slides em 750 px; moderno e wab em 350 px; slide 2 sem texto, sem camada, `transform` e `filter` `none`, `alt` com a descrição, miniatura `banner_desktop`; os slides com texto com camada e título. Defeito achado e corrigido: no moderno e no wab o slide "só a imagem" ficava com 0 px de altura (ver D3); depois da correção, 350 px como os outros.
- [x] 4.2 Refazer a comparação da 1.1 (`comparar.sh` e as páginas `so-imagem`): nenhuma diferença
  - `comparar-por-slide.sh comparar`: 17 de 17 idênticos (`/`, `com-texto` e `so-imagem` dos cinco sites, mais as homes de `127.0.0.1` e `localhost`), depois de devolver o slide 2 do bloco 25, ligado na 3.2, ao estado original. As páginas `so-imagem` são de blocos gravados com a chave antiga do bloco: a compatibilidade mantém o HTML igual.
- [x] 4.3 Atualizar a linha `banner` do `README.md` (opção por slide e banner misto); rodar `vendor/bin/phpunit` inteiro (só os 5 erros antigos do `SuperTestFieldsControllerTest`), `lint:twig` e `openspec validate banner-so-imagem-por-slide --strict`; anotar em "Medições"

## 5. Deploy (autorizado pelo Jonas: "faça tudo, ao testar, publique, arquive")

- [x] 5.1 Backup antes (leitura + escrita de backup no servidor): HTTP dos 10 domínios de cliente, home da Transmai guardada, HEAD e dump em `~/backups/`
- [ ] 5.2 Deploy (escrita): commit, `git push origin main`; no servidor, `git pull --ff-only` e `./build.sh` (template e PHP); sem migration
- [ ] 5.3 Conferência depois (leitura): HTTP 200 em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br, ajope.wab.com.br e transmai.wab.com.br; home da Transmai igual à de antes; em `/admin/block/147/edit` da Transmai, cada slide tem o interruptor "Só a imagem" (sem salvar)
- [ ] 5.4 Registrar no ticket interno da WAB (cliente 196, projeto 96), um registro para esta mudança e a `banner-so-imagem`, pela API, com print anotado, horas, status concluído e envio programado para 2 horas depois

## Medições

- `vendor/bin/phpunit`: 199 testes, 1.432 verificações, só os 5 erros antigos do `SuperTestFieldsControllerTest` (antes desta mudança: 181). Novos: `SlidesDoBannerTest` (10), `FiltrosDoLcpTest` (+2), `TextoDeResumoTest` (+2), `BannerSoImagemTest` (+4: misto nos cinco temas, todos por slide, LCP do misto, bloco antigo salvo pelo painel; gravação e prévia reescritas para o campo por slide).
- `lint:twig templates` (268 arquivos) e `lint:container` ok; `openspec validate banner-so-imagem-por-slide --strict` válida.

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido, já autorizado.
