# Tasks

> Leia antes: `design.md` desta mudança e, como base, `src/Service/SanfonaDoBloco.php`, `templates/admin/block/_form_accordion.html.twig` e os parciais `_accordion.html.twig` de cetec e atelie.

## 1. Envio de imagem do painel

- [x] 1.1 Criar `src/Service/MidiaDoBloco.php` (`gravar()` e `arquivosDoConfig()`, D2) e a rota `POST /admin/block/midia` no `AdminContentController`; teste funcional `tests/Controller/MidiaDoBlocoTest.php`: PNG válido grava e devolve o nome; PDF recusado com 400; token errado 403; sem login redireciona; verificar com `vendor/bin/phpunit --filter MidiaDoBloco`
- [x] 1.2 Filtro `logo_bloco` no `liip_imagine.yaml` e a pasta no `AquecerMiniaturasCommand` (D3); verificar com `bin/console debug:config liip_imagine filter_sets | grep -A4 logo_bloco`

## 2. Célula com imagem

- [x] 2.1 `SanfonaDoBloco::tabela()` aceita célula objeto `{text, image}` (D1); acrescentar ao `SanfonaDoBlocoTest`: célula só com imagem mantém a linha, nome de imagem com `/` ou `..` é descartado, célula string continua string; verificar com `vendor/bin/phpunit --filter SanfonaDoBloco`
- [x] 2.2 Editor da célula (D4) em `_form_accordion.html.twig`; verificar no navegador em `cetec.localhost`: escolher um logo numa célula, ver a miniatura e a prévia, remover a coluna de outra célula, colar planilha e desfazer, salvar e reabrir com o logo
- [x] 2.3 Parciais de cetec e atelie (D3, D6) e `BlocoSanfonaTest` com célula de imagem nos cinco temas (`<img` com `logo_bloco` e `alt`); verificar no navegador em 1280 e 390 px, claro e escuro

## 3. Exportação e importação

- [x] 3.1 `TenantExporter` e `TenantImporter` com `page_block_media` (D5); verificar exportando `cetec.localhost` com a sanfona de logos e importando como `sanfona-export.localhost`: logos aparecem; depois apagar o site importado pelo `/superadmin`

## 4. Integração

- [x] 4.1 `vendor/bin/phpunit` inteiro (só as falhas antigas do `SuperTestFieldsControllerTest`) e `openspec validate imagem-na-celula-da-sanfona --strict`; documentar no `README.md` (linha do `accordion`)

## 5. Deploy (junto com `bloco-mapa-de-representantes`)

- [x] 5.1 Backup do banco de produção, `git push`, no servidor `git pull` e `./build.sh` (sem migration); HTTP 200 nos 9 domínios

## Deploy

- 23/11/2026: backup `~/backups/wab-sites-antes-mapa-de-representantes-20261123-2114.sql.gz` (122 KB, permissão 600); commit `e93031a` no main; no servidor `git pull` e `./build.sh` (1.374 miniaturas; sem migration). HTTP 200 antes e depois em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br, ajope.wab.com.br e transmai.wab.com.br.
