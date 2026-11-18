# Proposal

## Why

O novo site da Marcenaria Pichoneri (layout em `openspec/changes/tema-atelie/referencia/proposta-redesign-marcenaria-pichoneri.html`) usa composições que nenhum dos 15 tipos de bloco atuais desenha: destaque com foto e selo, faixa de diferenciais, portfólio com filtro, manifesto com número, etapas numeradas, faixa de chamada e vídeo. Esses blocos precisam existir no construtor, e não só num tema, para que os sites que já rodam no tema cetec também possam usá-los.

Esta é a mudança 1 de 4 (ordem de execução: `blocos-novos-do-construtor` → `blog-nos-sites-institucionais` → `tema-atelie` → `site-marcenaria-pichoneri`). Ela não cria tema nem toca em conteúdo de cliente.

## What Changes

- Sete tipos de bloco novos no construtor, com formulário no `/admin` e desenho no tema cetec:
  - `hero_split` (Destaque com foto): texto à esquerda, foto à direita, selo com número e legenda sobre a foto.
  - `highlights` (Faixa de destaques): 2 a 4 itens de título + texto curto, em faixa.
  - `portfolio` (Portfólio com filtro): fotos com título e categoria, botões de filtro por categoria, primeira foto em destaque.
  - `statement` (Manifesto com imagem): texto sobre fundo escuro, número grande com legenda e foto sangrando até a borda.
  - `steps` (Etapas): até 6 passos numerados com rótulo, título e texto.
  - `cta` (Chamada para ação): faixa com pré-título, título e botão.
  - `video` (Vídeo): YouTube (horizontal ou vertical) ou MP4 enviado, com título e texto ao lado.
- Os temas `moderno` e `wab` passam a desenhar os sete tipos com o parcial do cetec (como o `noticias` já faz com todos os blocos); o `noticias` os recebe sem alteração.
- A foto de galeria ganha `título` e `categoria` (colunas novas, nulas), usados só pelo `portfolio`.
- Duplicação, exportação e importação de site passam a levar os dados dos blocos novos (título e categoria da foto, arquivo de vídeo).
- Menu do Modo Landing Page reconhece os tipos novos ao dar nome a uma seção sem título.
- Rede de proteção contra regressão: um script que fotografa o HTML das páginas das fixtures antes da mudança e compara depois, e um teste que garante que todo tipo de bloco tem formulário e desenho em todos os temas.
- Nada muda nos 15 tipos existentes nem no HTML das páginas que já existem.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: "Blocos por tipo" passa de 15 para 22 tipos; requisitos novos para cada bloco, para os dados da foto do portfólio e para a duplicação.
- `navegacao-e-aparencia`: "Menu do Modo Landing Page" ganha rótulos para os tipos novos.
- `noticias`: "Páginas institucionais no tema noticias" deixa de citar "15 tipos".
- `gestao-de-sites`: exportação e importação levam os dados dos blocos novos.

## Impact

- Código: `src/Entity/Enum/BlockType.php`, `src/Entity/PageBlockImage.php` (+ migration), `src/Controller/admin/AdminContentController.php` (`populateBlock`), `src/Service/DuplicatorService.php`, `src/Service/TenantExporter.php`, `src/Service/TenantImporter.php`, `src/Command/AquecerMiniaturasCommand.php`, `config/packages/liip_imagine.yaml`.
- Templates: `templates/admin/block/new.html.twig` + 7 `_form_<tipo>.html.twig`; `templates/themes/cetec/_block.html.twig` + 7 parciais em `themes/cetec/blocks/`; `_block.html.twig` de `moderno` e `wab`; `themes/cetec/_header.html.twig`.
- Fixtures novas em grupo próprio (`cetec.localhost`), sem alterar as existentes.
- Banco: duas colunas nulas em `page_block_image`. Nenhum dado existente muda.
- Produção: deploy com `git pull`, `./build.sh` e `doctrine:migrations:migrate` (escrita, com aprovação do Jonas).
