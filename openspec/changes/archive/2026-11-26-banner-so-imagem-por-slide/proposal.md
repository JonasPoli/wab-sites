# Proposal

## Why

A opção "Só a imagem" do bloco Banner (mudança `banner-so-imagem`, publicada em 26/11/2026) vale para todos os slides do bloco. O Jonas pediu, no mesmo dia, que ela possa ser escolhida slide a slide, para um carrossel misturar artes prontas (só a imagem) com slides de título, texto e botão.

## What Changes

- O interruptor "Só a imagem" sai do topo do formulário do banner e passa a existir em cada slide (`banners[].imageOnly`). Ligado num slide, só os campos de texto e botão daquele slide ficam inertes, e só ele mostra a "Descrição da imagem".
- Site, nos cinco temas:
  - todos os slides ativos "só a imagem": o banner sai exatamente como hoje (imagem inteira, na proporção do 1º slide, sem camada, texto ou botões);
  - slides dos dois tipos no mesmo banner: o banner segue o desenho e a altura dos slides com texto do tema, e o slide "só a imagem" mostra só a arte, preenchendo a área (recortada nas bordas quando a proporção for outra), sem camada, degradê, zoom, texto ou botões; o painel avisa isso;
  - nenhum slide "só a imagem": nada muda.
- Compatibilidade: bloco gravado com a opção do bloco ligada (`config.imageOnly`) continua com todos os slides "só a imagem"; ao salvar pelo painel, a escolha passa para cada slide e a chave do bloco sai.
- A descrição de SEO montada do conteúdo segue o primeiro slide ativo: "só a imagem" usa a descrição da imagem; com texto, o texto, como antes.
- Fora do escopo: altura própria por slide e imagem diferente para o celular.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: os requisitos do banner "só a imagem" passam a valer por slide; requisito novo para o banner com slides dos dois tipos.
- `seo-das-paginas`: "Texto do banner só com a imagem na descrição" passa a olhar o primeiro slide ativo.

## Impact

- Código: serviço novo `src/Service/SlidesDoBanner.php` (regra "este slide é só a imagem" e "todos os slides são"), extensão Twig nova com as funções para os temas, `AdminContentController::populateBlock()` (grava `imageOnly` por slide e tira a chave do bloco), `PreviaDoBloco::slides()`, `AtivosExtension::filtrosDoLcp()`, `TextoDeResumo`.
- Templates: `admin/block/_form_banner.html.twig` (interruptor por slide); parcial novo `themes/_comum/_slide_so_imagem.html.twig` (slide "só a imagem" dentro do carrossel com texto); o laço do carrossel de `themes/{cetec,moderno,wab,atelie}/blocks/_banner.html.twig` ganha o desvio por slide, sem mudar o HTML dos slides com texto; o desvio do topo passa a usar "todos os slides são só a imagem".
- Banco: nada novo (tudo em `page_block.config`); sem migration.
- Produção: deploy com `git pull` e `./build.sh`, autorizado pelo Jonas ("faça tudo, ao testar, publique, arquive"). O bloco 147 da Transmai não muda sozinho.
- Registro: ticket interno da WAB (cliente 196, projeto 96), junto com a `banner-so-imagem`.
