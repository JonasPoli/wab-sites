# Tasks

> Leia antes: `design.md` desta mudança (D1 traz as chaves do `config`). Modelos de código: `templates/admin/block/_form_steps.html.twig` e `_campos.html.twig` (formulário), `templates/themes/cetec/blocks/_steps.html.twig` e `themes/atelie/blocks/_steps.html.twig` (desenho), `src/Service/PreviaDoBloco.php` (prévia). Não altere os parciais dos outros 23 tipos.

## 1. Catálogo, normalização e filtro das células

- [x] 1.1 Em `src/Entity/Enum/BlockType.php`, acrescentar `Accordion = 'accordion'`, rótulo "Sanfona" e ícone `fa-solid fa-bars-staggered`; em `templates/admin/block/new.html.twig`, o cartão `{key: 'accordion', label: 'Sanfona', …, desc: 'Itens que abrem e fecham, com texto ou tabela'}`; verificar com `php -l` e `bin/console lint:container`
- [x] 1.2 Criar `src/Service/SanfonaDoBloco.php` com `normalizar(array $cfg): array` e `exemplos(): array` conforme D2, e `tests/Service/SanfonaDoBlocoTest.php` cobrindo: HTML com script e `onclick` limpo, JSON inválido vira tabela vazia, linha vazia removida, corte em 12 colunas e 500 linhas, linhas completadas até a maior largura, item sem título e sem conteúdo removido, item sem título com conteúdo mantido, `kind` desconhecido vira `text`; verificar com `vendor/bin/phpunit --filter SanfonaDoBloco`
- [x] 1.3 Criar `src/Twig/SanfonaExtension.php` com o filtro `celula_da_sanfona` (D4) e `tests/Twig/CelulaDaSanfonaTest.php` (escape de `<b>`, e-mail vira `mailto:`, `https://` e `www.` viram link, quebra de linha vira `<br>`, `&` escapado só uma vez); verificar com `vendor/bin/phpunit --filter CelulaDaSanfona`

## 2. Painel: gravação, formulário e prévia

- [x] 2.1 Em `AdminContentController::populateBlock()`, para `$type === 'accordion'`, gravar `config` passado por `SanfonaDoBloco::normalizar()`; em `PreviaDoBloco::montar()`, o mesmo, e com `panels` vazio usar `exemplos()`; verificar com o teste da 2.4
- [x] 2.2 Criar `templates/admin/block/_form_accordion.html.twig` conforme D7 (itens com subir/descer/remover, tipo Texto/Tabela, Quill por item, grade da tabela com + linha/+ coluna/remover, cabeçalho, área "Colar de planilha" com TSV e tabela HTML e "Desfazer colagem", JSON no campo oculto, evento `input` para a prévia); verificar no navegador em `cetec.localhost`: criar uma sanfona com um item de texto e um de tabela colada de uma planilha, salvar, reabrir e ver tudo de volta; reordenar e salvar; trocar tabela→texto→tabela sem perder a tabela
- [x] 2.3 Em `templates/admin/block/_previa.html.twig`, ignorar campos com `data-previa-vazio` na troca por rótulo; verificar na prévia da 2.2 que célula vazia fica vazia e título vazio mostra "Item N — Título do item"
- [x] 2.4 Criar `tests/Controller/BlocoSanfonaTest.php` (no molde do `PreviaDoBlocoTest`): grava pelo `POST /admin/block/{id}/edit` uma sanfona com 27 itens e 400 linhas de 7 colunas em JSON e confere tudo no banco; grava texto com `<script>` e confere que saiu; a prévia sem itens responde 200 com os dois exemplos; a prévia de outro site dá 404; verificar com `vendor/bin/phpunit --filter BlocoSanfona`

## 3. Desenho nos temas

- [x] 3.1 Criar `templates/themes/cetec/blocks/_accordion.html.twig` (D3, D5, D6) e o componente `.wab-sanfona` em `assets/styles/app.css` (claro, `.dark`, `.wab-section-dark`, `.mp-section-dark`, cartões abaixo de 640 px); acrescentar o `elseif` no `themes/cetec/_block.html.twig` e `'accordion'` na lista do `elseif t in [...]` de `themes/moderno/_block.html.twig` e `themes/wab/_block.html.twig`
- [x] 3.2 Criar `templates/themes/atelie/blocks/_accordion.html.twig` e as classes `at-accordion*` em `themes/atelie/_styles.html.twig`; acrescentar `'accordion'` à lista `tipos` do `themes/atelie/_block.html.twig`
- [x] 3.3 Rótulo "Informações" do menu (D9) em `themes/cetec/_header.html.twig` (desktop e celular), `themes/moderno/_header.html.twig`, `themes/wab/_header.html.twig` e no mapa `rotulos` do `themes/atelie/_header.html.twig`
- [x] 3.4 Acrescentar ao `BlocoSanfonaTest` uma página com sanfona em sites de teste dos temas cetec, moderno, wab, noticias e atelie: `/p/<slug>` responde 200, tem um `<details>` por item com título, `name` igual entre eles (e nenhum com "vários abertos"), `open` só no primeiro com "abrir o primeiro", `data-rotulo` nas células com cabeçalho, `mailto:` da célula com e-mail, e nenhum `<script>`; `vendor/bin/phpunit --filter BlockTypeCobertura` continua passando
- [x] 3.5 Acrescentar uma sanfona (um item de texto com lista e link, um de tabela com cabeçalho e 7 colunas, um terceiro de tabela sem cabeçalho) à página `todos-os-blocos` em `src/DataFixtures/BlocosDeExemplo.php`; no banco local, criar a mesma sanfona nas páginas já carregadas de `cetec.localhost`, `atelie.localhost`, `localhost` (moderno), `127.0.0.1` (wab) e `noticias.localhost` sem recarregar as fixtures (script em scratchpad que usa o kernel); conferir no navegador, em 1280 px e 390 px, claro e escuro (cetec, moderno, wab, noticias) e numa seção de fundo escuro do cetec: itens abrem e fecham, um de cada vez, tabela rola de lado no desktop estreito e vira cartões no celular, contraste legível

## 4. Markdown para agentes

- [x] 4.1 Registrar o `TableConverter` em `src/Agentes/MarkdownDaPagina.php` (D8); verificar com `curl -sk https://cetec.localhost:8000/p/todos-os-blocos.md` que a sanfona sai com todos os itens e a tabela em Markdown (`| Cidade | … |` e a linha `| --- |`) e que `https://cetec.localhost:8000/index.md` continua igual ao de antes da mudança (comparar com uma cópia salva antes da tarefa)

## 5. Documentação e integração

- [x] 5.1 Acrescentar ao `README.md` a linha `accordion` na tabela "Blocos (`PageBlock`)" (texto ou tabela por item, colar de planilha, `<details>`)
- [x] 5.2 Recompilar (`bin/console tailwind:build --minify && bin/console asset-map:compile`), medir `public/assets/styles/app-*.css` antes e depois e registrar em "Medições"; se crescer mais de 3 KB, enxugar o CSS da sanfona
- [x] 5.3 Conferir que o HTML das páginas existentes não mudou: `curl` de `/` e `/p/todos-os-blocos` (sem a sanfona criada na 3.5, ou só a seção nova de diferença) de `localhost`, `127.0.0.1` e `noticias.localhost` antes e depois, normalizando hash dos assets e tokens
- [x] 5.4 Rodar `vendor/bin/phpunit` inteiro (só as falhas antigas do `SuperTestFieldsControllerTest` são aceitas) e `openspec validate bloco-sanfona --strict`

## 6. Deploy (autorizado no pedido do Jonas: "publique")

- [x] 6.1 Backup (leitura + escrita de arquivo no servidor): `mysqldump` do banco de produção para `~/backups/` no wabsites; conferir o `post_max_size` e o `max_input_vars` do pool PHP 8.3 (leitura) e anotar o HTTP atual dos domínios de cliente
- [x] 6.2 Deploy (escrita): commit, `git push origin main`; no servidor, como runcloud, em `~/webapps/app-wab-sites`: `git pull` e `./build.sh` (sem migration)
- [ ] 6.3 Conferência: HTTP 200 em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br e ajope.wab.com.br; no `/admin` de um site, a escolha de tipo mostra "Sanfona" e o formulário abre

## Medições

- `app.css` minificado: antes `app-eHqjZsi.css` 78.681 bytes (12.374 gzip); depois `app-5cxeY3-.css` 81.754 bytes (13.234 gzip): +3.073 bytes (+860 gzip). A primeira versão do CSS dava +4,4 KB; foi enxugada (variáveis curtas, `:is()`, sem alinhamento do Quill).
- O atelie põe o CSS no `<style>` da página: o HTML de todo site atelie cresce ~3,5 KB (antes do gzip).
- Regressão (5.3): homes de localhost, 127.0.0.1, noticias.localhost e cetec.localhost idênticas às de antes (normalizando hash de asset e barra de depuração); `/index.md` dos cinco sites idêntico; as páginas `todos-os-blocos` só mudam pela seção de sanfona criada na 3.5.
- `vendor/bin/phpunit`: 122 testes, 117 passam; os 5 que falham são os do `SuperTestFieldsControllerTest`, já desatualizados antes desta mudança.
- Conferência visual (3.5) feita num servidor HTTP local em 127.0.0.1:8011 (o Chrome não confia no certificado do `symfony server`): cetec claro, escuro e seção escura; moderno claro e escuro; wab escuro; noticias claro e escuro; atelie; celular (390 px) no cetec e no atelie, sem rolagem lateral. Defeito achado e corrigido: no modo cartão, cada trecho da célula virava item da grade (conteúdo da célula agora vai num `<span>`).
- Painel (2.2): colar tabela HTML e TSV com célula entre aspas, colar várias células numa célula, trocar tabela→texto→tabela, subir item, novo item com Quill, prévia atualizada e gravação conferida no banco.

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido.

## Deploy

- 6.1 (23/11/2026): backup `~/backups/wab-sites-antes-bloco-sanfona-20261123-2016.sql.gz` (120 KB, permissão 600). PHP CLI do servidor: `max_input_vars` 1000, `post_max_size` 512M (o pool FPM não é legível pelo runcloud; a tabela vai num campo JSON por item, então o limite de campos não pesa). HTTP antes: 200 nos 9 domínios.
- 6.2 (23/11/2026): commit `0b5ef08` no main; no servidor, `git pull` (de `d8f66c1`) e `./build.sh` concluído (1.325 miniaturas refeitas); sem migration. O `sh/reset.sh` alterado no servidor (fora do git) não foi tocado.
- 6.3: HTTP 200 nos 9 domínios depois do deploy; os sites servem `app-5cxeY3-.css` (o mesmo compilado localmente, com `.wab-sanfona`); `lint:twig` dos 3 templates novos ok e `SanfonaDoBloco` no contêiner de produção. **Pendente:** abrir no `/admin` de um site a escolha de tipo e o formulário da Sanfona (precisa de login em produção, que não foi feito aqui).
