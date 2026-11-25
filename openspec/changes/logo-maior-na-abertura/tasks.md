# Tasks

> Leia antes: `design.md` (D2 traz os seletores de cada tema). Modelos: o botão flutuante (`Tenant::getFloatingButton()`, migration com `doctrine:migrations:diff`) e `tests/Controller/BotaoFlutuanteNoSiteTest.php`.

## 1. Dados

- [x] 1.1 Coluna `headerLogo` em `Tenant` (D1), migration por `doctrine:migrations:diff`, aplicada no banco local e no de teste; exportação e importação; verificar com `doctrine:schema:validate` e com o teste de ida e volta da 3.2

## 2. Desenho nos temas

- [x] 2.1 Salvar em scratchpad o HTML de `/` dos sites locais dos cinco temas (cetec, atelie, localhost, 127.0.0.1, noticias)
- [x] 2.2 Filtro `logo_site_grande` (D3) e aquecimento; parcial `themes/_comum/_logo_grande.html.twig` (D2) incluído nos cinco `_header.html.twig`; troca da miniatura do logo do cabeçalho no cetec, atelie e noticias com a opção ligada; variável no CSS do moderno; verificar com `bin/console lint:twig templates/themes`
- [x] 2.3 Regressão: o HTML salvo na 2.1 igual ao de depois (opção desligada), ignorando espaços, tokens e barra de depuração; no moderno, só a soma da variável no CSS
- [x] 2.4 Conferir no navegador, nos cinco temas, com a opção ligada em 120 px: logo com 120 px no topo (56→120 no cetec), 90 px depois de 30 px de rolagem no cetec, altura normal depois de 300 px, de volta a 120 px no topo; 390 px de largura com metade do aumento; moderno com a barra superior ligada sem conteúdo escondido; rodapé igual

## 3. Edição e testes

- [x] 3.1 Grupo "Logo na abertura" em `superadmin/tenant/edit.html.twig` com prévia e gravação em `populateTenantFromRequest()` (D4); verificar no navegador que a prévia acompanha o controle e que salvar e reabrir traz os valores
- [x] 3.2 Criar `tests/Controller/LogoGrandeTest.php`: opção desligada não emite o parcial; ligada emite, nos cinco temas, o `<style>` com a altura certa e o script, e o cetec usa a miniatura `logo_site_grande`; POST pelo `/superadmin` grava, limita 500 a 200, sem `headerLogoForm` mantém; exportação e importação levam a escolha; verificar com `vendor/bin/phpunit --filter LogoGrande`
- [x] 3.3 README (seção do Tenant) com a opção

## 4. Integração

- [x] 4.1 `vendor/bin/phpunit` inteiro (só as falhas antigas do `SuperTestFieldsControllerTest`) e `openspec validate logo-maior-na-abertura --strict`

## 5. Deploy (só com a confirmação do Jonas)

- [x] 5.1 Backup (escrita de arquivo no servidor): `mysqldump` do banco de produção para `~/backups/`; HTTP atual dos domínios
- [x] 5.2 Deploy (escrita): commit, `git push origin main`; no servidor, `git pull`, `./build.sh` e `bin/console doctrine:migrations:migrate --no-interaction`
- [x] 5.3 Conferência (leitura): HTTP 200 em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br e ajope.wab.com.br (e pichoneri e transmai); nenhum site com o parcial do logo grande; `header_logo` nulo em todos os tenants

## Medições

- Testes: `tests/Controller/LogoGrandeTest.php` (4 testes, 55 verificações: CSS e script nos cinco temas com a diferença certa, metade no celular, miniatura `logo_site_grande` só no cabeçalho e só com a opção ligada, gravação pelo `/superadmin` com limite e sem o grupo, exportação e importação). `vendor/bin/phpunit` inteiro: 169 testes, só as 5 falhas antigas do `SuperTestFieldsControllerTest`; nada sobrou no banco de teste nem em `public/uploads`.
- Regressão (2.3): com a opção desligada, as homes de cetec, atelie, 127.0.0.1 e noticias são idênticas às de antes; no moderno, só a soma de `var(--wab-logo-extra, var(--wab-logo-d, 0px))` em três regras (vale 0 sem a opção).
- Navegador (2.4, 3.1), `php -S` em 127.0.0.1:8011, opção ligada em 120 px com um logo local (283×283): cetec 120 → 90 (30 px rolados) → 56 (64 px) → 120 de volta no topo; moderno 120 → 80 → 40, conteúdo começando logo abaixo do cabeçalho, e com a barra superior ligada barra em 0–40, cabeçalho em 40–191, conteúdo em 190; wab 120 → 86 → 52; noticias 116 → 78 → 40 (logo no fundo branco, 4 px menor que o normal); atelie 120 → 89 → 58. Em 390 px: cetec 84 → 48, atelie 83 → 46, wab 86 → 52, noticias 78 → 40, moderno 80 → 40, com o botão do menu dentro da tela. Na tela do `/superadmin`, a caixa abre o painel, a faixa e o número andam juntos, a prévia mostra as duas alturas e salvar grava `{"large": true, "height": 120}`. Os sites locais voltaram à configuração de antes.
- Defeitos achados e corrigidos na conferência: (1) o *scroll anchoring* do navegador devolvia a rolagem que o cabeçalho perdia (rolar 30 px voltava a 0); o parcial põe `overflow-anchor: none` no `html` e no `body`, porque no noticias o `overflow-x` do `body` passa para a janela e o navegador lê o `overflow-anchor` do `body`. (2) No atelie em 390 px, o logo maior empurrava o menu 18 px para fora, porque o `.at-logo` não encolhia; com a opção, ele passa a `flex-shrink: 1`.
- Já existiam antes da mudança (não vêm do cabeçalho): 8 px de rolagem lateral no cetec e no wab em 390 px (bloco `w-screen` dentro de iframe com barra de rolagem) e 70 px no moderno (elementos decorativos `orb` e a caixa de contato).
- Em `php -S` sem roteador, a URL `/media/cache/resolve/…png` não chega ao Symfony; a miniatura nova foi gerada com `liip:imagine:cache:resolve`. Em produção, o nginx passa essa URL ao Symfony.

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido.

## Deploy

- 5.1 (25/11/2026, publicação confirmada pelo Jonas): backup `~/backups/wab-sites-antes-logo-maior-20261125-1945.sql.gz` (dump completo, permissão 600); HTTP 200 nos 11 domínios.
- 5.2: push de `6f06b43`; no servidor, `git pull` (de `e6fcabe`), `doctrine:migrations:migrate` (Version20261125183242, 1 comando SQL) e `./build.sh` concluído.
- 5.3: HTTP 200 nos 11 domínios; `lint:twig` do parcial ok em produção. Nenhum site com o logo grande, exceto a Transmai (tenant 13, cetec), que o Jonas ligou logo depois do deploy com 100 px (`--wab-logo-d` 44 px no desktop e 26 px no celular); o logo do cabeçalho sai pela miniatura `logo_site_grande` (200, WebP de 33 KB).

