# Tasks

> Leia antes: `design.md` desta mudança e a change `imagem-na-celula-da-sanfona` (envio de imagem e filtro `logo_bloco`), que precisa estar implementada.

## 1. Mapa e dados

- [x] 1.1 `bin/mapa-do-brasil.py` (D1) e `templates/themes/_comum/_mapa_do_brasil.html.twig` gerado com os 27 estados; verificar renderizando o SVG e conferindo os 27 `data-mapa-uf`
- [x] 1.2 `BlockType::Representatives` ("Mapa de representantes", `fa-solid fa-map-location-dot`) e o cartão em `admin/block/new.html.twig`; verificar com `php -l` e `lint:container`
- [x] 1.3 `src/Service/MapaDeRepresentantes.php` (D2), `tel:` no `ArticleBodySanitizer`, `reps[].image` no `MidiaDoBloco`; `tests/Service/MapaDeRepresentantesTest.php` (UF inválida descartada, `h2` vira `h3`, script removido, `tel:` mantido, imagem com `/` descartada, registro sem texto e sem imagem descartado, ordem mantida); verificar com `vendor/bin/phpunit --filter MapaDeRepresentantes`

## 2. Painel

- [x] 2.1 Gravação em `populateBlock()` e prévia em `PreviaDoBloco` (D6)
- [x] 2.2 `admin/block/_form_representatives.html.twig` (D6); verificar no navegador em `cetec.localhost`: criar mapa, cadastrar 3 registros (dois na Bahia) com logo e título `h3`, filtrar, mover, remover, ver a prévia acompanhar; salvar e reabrir

## 3. Desenho

- [x] 3.1 Script comum (D3) e parcial do cetec (D4) com o CSS `.wab-mapa`; `'representatives'` nos despachantes de cetec, moderno e wab
- [x] 3.2 Parcial e estilos do atelie (D5); `'representatives'` no despachante do atelie
- [x] 3.3 Rótulo "Representantes" nos cabeçalhos (D7)
- [x] 3.4 `tests/Controller/BlocoMapaDeRepresentantesTest.php`: nos cinco temas responde 200, 27 `data-mapa-uf`, um cartão por UF com registro, cartão "vazio" com a mensagem, nenhum cartão com `hidden` no HTML, logo com `logo_bloco`; gravação pelo painel de 120 registros; prévia sem registros com os exemplos; prévia de outro site 404; `BlockTypeCobertura` passa
- [x] 3.5 Exemplo em `BlocosDeExemplo` e, no banco local, um mapa nas páginas dos 5 sites das fixtures; conferir no navegador em 1280 e 390 px, claro, escuro e seção escura: clique, teclado, seletor, estado sem registro, `#` no endereço, sem rolagem lateral

## 4. Integração

- [x] 4.1 Medir o `app.css` (limite +2,5 KB) e registrar em "Medições"; `vendor/bin/phpunit` inteiro; `openspec validate bloco-mapa-de-representantes --strict`; README (tabela de blocos)

## 5. Deploy (junto com `imagem-na-celula-da-sanfona`)

- [ ] 5.1 Backup do banco de produção; `git push`; no servidor `git pull` e `./build.sh` (sem migration); HTTP 200 nos 9 domínios e em transmai.wab.com.br

## Medições

- `app.css` minificado: antes destas duas changes `app-5cxeY3-.css` 81.754 bytes (13.234 gzip); depois `app-Nxyn4jV.css` 84.709 bytes (14.098 gzip): +2.955 bytes (+864 gzip), sendo ~2,7 KB do `.wab-mapa` e ~0,3 KB da imagem na célula da sanfona. O SVG do mapa (23 KB, ~8 KB com gzip) só vai no HTML das páginas com o bloco.
- Conferência no navegador (servidor HTTP local em 127.0.0.1:8011): cetec claro e escuro, moderno (cores verdes do site), atelie (computador, MG por `#`), wab e noticias só pelo teste funcional e HTTP 200, celular 390 px no cetec (DF pelo seletor, sem rolagem lateral), seção de fundo escuro (simulada com `mp-section-dark`), teclado (Tab até os estados, contorno de foco, Enter abre a Bahia), clique na Bahia, link direto `#mapa-189-SP`, painel com filtro BA, registro novo herdando o estado do filtro, mover, salvar (86 registros, `h2` → `h3`, `tel:` mantido).
- Pedido do Jonas durante a implementação: quando algum registro do estado tem logo, os sem logo ganham o mesmo recuo (requisito "Alinhamento dos registros do estado"); conferido no Amazonas.
- `vendor/bin/phpunit`: 136 testes, só as 5 falhas antigas do `SuperTestFieldsControllerTest`.
