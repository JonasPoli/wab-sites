# Tasks

> Leia antes: `design.md` (D1 traz o formato do JSON, D7 e D8 o desenho). Modelos: o seletor de home em `SuperAdminController::populateTenantFromRequest()` (isolamento da página), o `icon-picker-wrap` de `templates/admin/block/_form_blurbs4.html.twig` e o teste `tests/Controller/BlocoSanfonaTest.php` (sites de teste nos cinco temas).

## 1. Dados e resolução do botão

- [x] 1.1 Em `src/Entity/Tenant.php`, coluna `floatingButton` (JSON, nula), `getFloatingButton()` normalizado (D1), `setFloatingButton()` e a chave em `__serialize`/`__unserialize`; gerar a migration com `bin/console doctrine:migrations:diff`, acrescentar o `UPDATE` dos temas moderno, wab e noticias (D2) e aplicar com `doctrine:migrations:migrate`; verificar com `doctrine:schema:validate` e um `SELECT id, theme, floating_button FROM tenant` local (noticias, moderno e wab com `none`, cetec e atelie nulos)
- [x] 1.2 Criar `src/Service/BotaoFlutuante.php` (D6) e a função Twig `botao_flutuante()`; criar `tests/Service/BotaoFlutuanteTest.php` cobrindo: nulo = WhatsApp, WhatsApp sem link = sem botão, Nenhum = sem botão, formulário com página e texto, formulário sem texto, página de outro tenant e página inexistente = sem botão, página home = URL da home; verificar com `vendor/bin/phpunit --filter BotaoFlutuante`

## 2. Edição no `/superadmin`

- [x] 2.1 Mover o CSS, o modal e o JavaScript do seletor de ícones de `admin/wab_base.html.twig` para `admin/_seletor_de_icones.html.twig` (D5) e incluí-lo no `wab_base`; verificar no navegador, no `/admin` de `cetec.localhost`, que o "Escolher" de um item do Blurbs4 abre o modal, busca e grava o ícone
- [x] 2.2 Gravação em `populateTenantFromRequest()` conforme D3
- [x] 2.3 Grupo "Botão flutuante" em `superadmin/tenant/edit.html.twig` (D4), com o CSS do FontAwesome e o parcial do seletor; verificar no navegador: escolher Formulário mostra os campos, o seletor abre e preenche o ícone, a prévia acompanha, salvar e reabrir traz tudo de volta
- [x] 2.4 Criar `tests/Controller/BotaoFlutuanteNoSiteTest.php` com o POST de edição pelo `/superadmin` (logado como um SuperAdmin do banco local): grava formulário, recusa página de outro tenant, tipo desconhecido vira WhatsApp, cadastro sem o campo mantém a escolha; verificar com `vendor/bin/phpunit --filter BotaoFlutuanteNoSite`

## 3. Desenho nos temas

- [x] 3.1 Antes de mexer nos temas, salvar em scratchpad o HTML de `/` de `cetec.localhost`, `atelie.localhost`, `localhost`, `127.0.0.1` e `noticias.localhost`
- [x] 3.2 Criar `themes/_comum/_botao_flutuante.html.twig` (D7, D8), incluí-lo nos `base.html.twig` de cetec, moderno, wab, noticias e atelie e tirar o bloco antigo do WhatsApp e o CSS `.whatsapp-float` do cetec; verificar com `bin/console lint:twig templates/themes`
- [x] 3.3 Acrescentar ao `tests/Controller/BotaoFlutuanteNoSiteTest.php` sites de teste nos cinco temas: WhatsApp aparece com link e some sem ele; Nenhum não mostra nada; Formulário mostra o link para `/p/<slug>`, o texto e o SVG do ícone, não mostra o WhatsApp e some na própria página de destino; verificar com `vendor/bin/phpunit --filter BotaoFlutuanteNoSite`
- [x] 3.4 Regressão: comparar o HTML salvo na 3.1 com o de depois (normalizando hash de asset e barra de depuração): cetec e atelie só podem mudar pela posição do `<style>` do botão; moderno, wab e noticias sem diferença
- [x] 3.5 Conferir no navegador, em 1280 px e 390 px, o botão de formulário no cetec (claro, escuro), moderno, wab, noticias e atelie, com uma cor primária clara e uma escura: contraste legível, sem cobrir o conteúdo, link correto

## 4. Exportação, importação e ícones

- [x] 4.1 `TenantExporter`, `TenantImporter` (reconectar `pageId` pelo `$pageMap`) e `SincronizarIconesCommand` conforme D9; verificar com `bin/console app:icones:sincronizar --listar` mostrando o ícone do botão de um tenant local e com um teste no `BotaoFlutuanteNoSiteTest` que exporta um site com formulário e importa com outro domínio
- [x] 4.2 Acrescentar ao `README.md` (seção do Tenant) o botão flutuante e seus três tipos

## 5. Integração

- [x] 5.1 Rodar `vendor/bin/phpunit` inteiro (só as falhas antigas do `SuperTestFieldsControllerTest` são aceitas) e `openspec validate botao-flutuante-do-site --strict`

## 6. Deploy (pedido do Jonas: "publique")

- [x] 6.1 Backup (escrita de arquivo no servidor): `mysqldump` do banco de produção para `~/backups/` no wabsites; anotar o HTTP atual dos domínios de cliente
- [x] 6.2 Deploy (escrita): commit, `git push origin main`; no servidor, como runcloud, em `~/webapps/app-wab-sites`: `git pull`, `./build.sh` e `bin/console doctrine:migrations:migrate --no-interaction`
- [x] 6.3 Conferência (leitura): HTTP 200 em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br e ajope.wab.com.br; `SELECT id, theme, floating_button FROM tenant`; o botão do WhatsApp continua nos sites cetec que têm link e o ajope não ganhou botão

## Medições

- Testes: `tests/Service/BotaoFlutuanteTest.php` (9 testes, resolução e normalização) e `tests/Controller/BotaoFlutuanteNoSiteTest.php` (6 testes, 99 verificações: WhatsApp, Nenhum e Formulário nos cinco temas, oculto na página de destino, cor do texto pela primária, página excluída, gravação pelo `/superadmin` com página de outro tenant e tipo desconhecido, exportação e importação). `vendor/bin/phpunit` inteiro: 152 testes, só as 5 falhas antigas do `SuperTestFieldsControllerTest`. O banco de teste (`.env.test.local`) recebeu a migration nova; nenhum tenant `bf-*` nem SuperAdmin de teste sobrou.
- Regressão (3.4): homes de `cetec.localhost`, `atelie.localhost`, `localhost`, `127.0.0.1` e `noticias.localhost` comparadas antes e depois (normalizando tokens e barra de depuração): no cetec só muda o lugar do CSS `.whatsapp-float` (do `<head>` para junto do botão, mesmas regras); nos outros, só espaços em branco. O botão do WhatsApp continua igual no cetec e no atelie.
- Navegador (2.1, 2.3, 3.5), servidor `php -S` em 127.0.0.1:8011: no `/superadmin` do cetec local, marcar Formulário mostra os campos, o seletor abre com busca, "envelope" preenche o ícone e a prévia, e salvar grava `{"mode":"form","pageId":33,"label":"Solicite um orçamento","icon":"fa-solid fa-envelope"}`; no `/admin` do cetec, o seletor do Blurbs4 continua abrindo e preenchendo o campo. Botão de formulário visto no cetec (claro `#9b4509` e escuro `#3b82f6`, texto branco), wab (escuro), moderno (claro, `#008800`), noticias (escuro) e atelie; em 390 px (atelie) a pílula fica a 16 px da borda sem rolagem lateral. Os sites locais voltaram à configuração de antes.
- `app:icones:sincronizar --listar` passou a listar os ícones do botão (`fa6-solid:envelope`, `comments`, `pen-ruler` durante a conferência).

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido.

## Deploy

- 6.1 (25/11/2026): backup `~/backups/wab-sites-antes-botao-flutuante-20261125-1647.sql.gz` (140 KB, permissão 600, dump completo). HTTP antes: 200 em todos os domínios (os 9 da lista, mais pichoneri.wab.com.br e transmai.wab.com.br). Leitura dos tenants: o único site de tema sem botão (ajope, noticias) não tinha WhatsApp, então a migration não muda nada visível.
- 6.2 (25/11/2026): commit `7404177` no main e push; no servidor, `git pull` (de `e93031a`), `doctrine:migrations:migrate` (Version20261125162223, 2 comandos SQL) e `./build.sh` concluído (1.526 miniaturas refeitas). O `sh/reset.sh` alterado no servidor (fora do git) não foi tocado.
- 6.3: HTTP 200 nos 11 domínios; o botão do WhatsApp aparece nos mesmos sites de antes (marapossi, pkwy, cemporcentoexpresso, imperiodainformatica, topdrim e pichoneri) e em nenhum outro; nenhum site mostra o botão de formulário; `floating_button` nulo em todos os tenants, menos o 10 (ajope), com `none`; `lint:twig` dos 3 templates novos ok em produção. A tela `/superadmin/tenant/13/edit` em produção não foi aberta (pede login); fica para o Jonas conferir antes de arquivar.

