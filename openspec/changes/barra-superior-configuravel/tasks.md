# Tasks

> Leia antes: `design.md` (D1 formato, D2 resolução, D5 editor). Modelos: `App\Service\BotaoFlutuante` (serviço + função Twig com `ResetInterface`), `tests/Controller/BotaoFlutuanteNoSiteTest.php` (sites de teste nos cinco temas e POST pelo `/superadmin`).

## 1. Serviço

- [x] 1.1 Criar `src/Service/BarraSuperior.php` (D2) e a função Twig `barra_superior()`; criar `tests/Service/BarraSuperiorTest.php` cobrindo: formato antigo lido como itens, tipo desconhecido descartado, custom sem ícone e sem texto descartado, limite de 10, texto de 80, `https://` no link, cor inválida vira vazia, dado vazio omite o item, WhatsApp formatado (celular, fixo e número não reconhecido), endereço com link do Maps, texto próprio substitui o texto sem mudar o link, redes só as preenchidas; verificar com `vendor/bin/phpunit --filter BarraSuperiorTest`

## 2. Desenho nos temas

- [x] 2.1 Antes de mexer nos temas, salvar em scratchpad o HTML de `/` de `cetec.localhost`, `atelie.localhost` (barra ligada nas fixtures), `localhost`, `127.0.0.1` e `noticias.localhost`
- [x] 2.2 Reescrever `themes/_topbar.html.twig` (D3), incluí-lo no `themes/noticias/_header.html.twig` e ajustar o moderno (D4); verificar com `bin/console lint:twig templates/themes`
- [x] 2.3 Regressão: comparar o HTML salvo na 2.1 com o de depois (normalizando tokens e barra de depuração): nenhuma diferença na barra do atelie; sem diferença nos outros (barra desligada)
- [x] 2.4 Criar `tests/Controller/BarraSuperiorNoSiteTest.php`: nos cinco temas, a barra com itens dos dois lados aparece na ordem, com os links certos (tel, wa.me, mailto, Maps, personalizado em nova aba); barra desligada não aparece; cor escolhida com texto branco; formato antigo desenhado; verificar com `vendor/bin/phpunit --filter BarraSuperiorNoSite`

## 3. Editor e gravação

- [x] 3.1 Criar `admin/_editor_da_barra_superior.html.twig` (D5) e usá-lo no `admin/settings.html.twig` e no `superadmin/tenant/edit.html.twig` no lugar das caixas; gravação nos dois controladores (D6)
- [x] 3.2 Acrescentar ao `BarraSuperiorNoSiteTest`: POST pelo `/admin/settings` e pelo `/superadmin` com `topBarJson` grava os itens normalizados e `topBarBg`; POST sem `topBarJson` mantém a barra; salvar pelo `/superadmin` mantém `customMenuItems` e `headerCta*`; verificar com `vendor/bin/phpunit --filter BarraSuperiorNoSite`
- [x] 3.3 Conferir no navegador, no `/admin/settings` de `cetec.localhost`: montar a barra do exemplo do Cetec (Endereço "Araraquara", Telefone, WhatsApp, Redes sociais à esquerda; personalizado "Portal do Aluno" com ícone à direita; cor `#3d3e90`), subir, descer, passar de lado, remover, escolher ícone no seletor, salvar e reabrir; depois ver o site em 1280 px e 390 px e rolar a página no cetec e no moderno

## 4. Ícones e documentação

- [x] 4.1 `SincronizarIconesCommand` lê os ícones custom da barra (D7); verificar com `bin/console app:icones:sincronizar --listar`
- [x] 4.2 Acrescentar ao `README.md` a seção da barra superior (tipos, editor, formato gravado)

## 5. Integração

- [x] 5.1 Rodar `vendor/bin/phpunit` inteiro (só as falhas antigas do `SuperTestFieldsControllerTest` são aceitas) e `openspec validate barra-superior-configuravel --strict`

## 6. Deploy (pedido do Jonas: "publique")

- [ ] 6.1 Backup (escrita de arquivo no servidor): `mysqldump` do banco de produção para `~/backups/`; anotar o HTTP atual dos domínios e salvar o HTML da barra da Pichoneri
- [ ] 6.2 Deploy (escrita): commit, `git push origin main`; no servidor, como runcloud, em `~/webapps/app-wab-sites`: `git pull` e `./build.sh` (sem migration)
- [ ] 6.3 Conferência (leitura): HTTP 200 em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br e ajope.wab.com.br (e pichoneri e transmai); a barra da Pichoneri igual à salva na 6.1; nenhum outro site com barra

## Medições

- Testes: `tests/Service/BarraSuperiorTest.php` (9 testes) e `tests/Controller/BarraSuperiorNoSiteTest.php` (4 testes: barra completa nos cinco temas na ordem gravada, com tel, wa.me, Maps, redes e personalizado em nova aba e SVG do ícone desenhado; desligada; cor própria; formato antigo; gravação pelo `/admin/settings` e pelo `/superadmin`, que mantém itens de menu e botão de ação, e POST sem `topBarJson` mantém a barra). `vendor/bin/phpunit` inteiro: 165 testes, só as 5 falhas antigas do `SuperTestFieldsControllerTest`; nenhum tenant `bs-*` nem usuário de teste sobrou.
- Regressão (2.3): homes de `cetec.localhost`, `atelie.localhost` (barra ligada, formato antigo), `127.0.0.1` e `noticias.localhost` idênticas às de antes, ignorando espaços, tokens e barra de depuração; em `localhost` (moderno) só muda o CSS do cabeçalho (D4). Na `pichoneri.localhost`, o `<div>` vazio de redes sociais (o site local não tem redes) deixou de ser desenhado, como pede "Dado vazio"; a Pichoneri de produção tem redes, então nada muda lá.
- Navegador (3.3), `php -S` em 127.0.0.1:8011: no `/admin/settings` do cetec local, a barra do exemplo do Cetec (Endereço "Araraquara", Telefone, WhatsApp e Redes à esquerda; "Portal do Aluno" com ícone `fa-solid fa-user-lock` escolhido no seletor, em nova aba, à direita; cor `#3d3e90`) foi montada com acrescentar, passar de lado e remover, salva e conferida no banco (link gravado com `https://`). No site: barra roxa com texto branco, itens na ordem. Rolagem de 300 px: a barra sai e o cabeçalho fica em 0 px no cetec, wab e noticias; no moderno, o cabeçalho acompanha a barra (40 → 20 → 0 px). Em 390 px (cetec), só os ícones. No `/superadmin` do atelie local, o formato antigo abre como três itens. Os sites locais voltaram à configuração de antes.
- Defeitos achados e corrigidos na conferência: o SVG do ícone personalizado saía escapado (o teste não pegava; agora pega); no moderno, o `padding-top` do `body` punha a barra abaixo do cabeçalho fixo (defeito antigo do tema, nunca usado com a barra).
- `app:icones:sincronizar --listar` lista `fa6-solid:user-lock` com um item personalizado com esse ícone.

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido.
