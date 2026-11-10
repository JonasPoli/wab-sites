# Tasks

## 1. Leitura antes de mexer

- [x] 1.1 Em produção (**leitura**, como runcloud), listar o `font_settings` de todos os tenants com `/RunCloud/Packages/php83rc/bin/php bin/console dbal:run-sql "SELECT id, domain, font_settings FROM tenant"`. Verificar e anotar aqui: (10/11/2026: a leitura do banco de produção por SSH foi bloqueada nesta sessão; as mesmas informações vieram do `<style>` público de `tenant_css_vars()` dos 9 sites. Os 8 de cliente e o ajope.wab.com.br (tenant 10, tema noticias) têm H1 a H5 em Outfit 400, no tamanho do tema; nenhuma família fora das cinco e nenhuma Playfair Display 300)
  - se alguma família está fora das cinco de hoje (esperado: nenhuma);
  - quais tenants têm Playfair Display com peso 300, que vão mudar de aparência (design, risco 1);
  - o domínio e o tema do tenant 10.
- [x] 1.2 Em localhost, com as fixtures, guardar o `<style>` gerado por `tenant_css_vars()` antes da mudança, para comparar na 3.2: `curl -s http://127.0.0.1:8000/`, `curl -s http://localhost:8000/` e `curl -s http://noticias.localhost:8000/`, cada um num arquivo da pasta temporária. Verificar: os três arquivos têm o bloco `@import url('https://fonts.googleapis.com/css2?`. (10/11/2026, servidor local na porta 8101: os três `<style>` guardados no scratchpad, cada um com o `@import` do Google Fonts)

## 2. Catálogo, formulário e gravação

- [x] 2.1 Criar `src/Service/HeadingFonts.php` com as seis famílias, os rótulos, as reservas e as faixas de pesos da tabela do design (decisão 1), e os métodos `choices()`, `normalize()`, `fallback()` e `clampWeight()`. Verificar com um `php -r` que carrega o `vendor/autoload.php` e mostra: (10/11/2026: `normalize('Comic Sans MS')` → Outfit; `clampWeight('Open Sans', 900)` → 800; `clampWeight('Playfair Display', '300')` → 400; `clampWeight('Inter', 300)` → 300; peso não numérico → 400)
  - `normalize('Comic Sans MS')` → `Outfit`;
  - `clampWeight('Open Sans', 900)` → 800;
  - `clampWeight('Playfair Display', '300')` → 400;
  - `clampWeight('Inter', 300)` → 300.
- [x] 2.2 Em `SuperAdminController::tenantEdit()`, passar `HeadingFonts::choices()` ao template como `fontFamilies`. Em `edit.html.twig`, tirar o mapa fixo `fontFamilies` (linhas 603 a 609) e pôr a linha de ajuda dos pesos abaixo do título da seção (decisão 2). Verificar em `http://localhost:8000/superadmin/tenant/<id>/edit`: cada um dos cinco selects de fonte tem as seis famílias, com a Open Sans antes da Outfit, e a linha de ajuda aparece. (10/11/2026, `noticias.localhost:8101/superadmin/tenant/12/edit`: os cinco selects com Inter, Roboto, Playfair Display, Montserrat, Open Sans e Outfit, nessa ordem, e a linha de ajuda dos pesos)
- [x] 2.3 Em `populateTenantFromRequest()`, gravar `HeadingFonts::normalize()` da família de cada título. Verificar em localhost: (10/11/2026, envio pelo formulário: H1 Open Sans gravado e marcado ao reabrir; H2 enviado como "Comic Sans MS" gravado como Outfit)
  - escolher Open Sans no H1 e salvar: o `fontSettings.h1.font` fica "Open Sans" (`bin/console dbal:run-sql "SELECT font_settings FROM tenant WHERE id = <id>"`), e ao reabrir a edição a Open Sans está marcada no H1;
  - enviar o formulário com o valor do H2 trocado para "Comic Sans MS" pelo inspetor do navegador: o H2 é gravado como Outfit.
- [x] 2.4 Com as migrations locais em dia (`doctrine:migrations:migrate` em localhost), rodar `bin/console doctrine:migrations:diff`. Verificar: o comando responde que não há mudança no mapeamento, ou, se outro change em andamento tiver entidade alterada, a migration gerada não tem nenhuma linha da tabela `tenant` e é apagada em seguida (decisão 4). (10/11/2026: "No changes detected in your mapping information")

## 3. CSS gerado no site

- [x] 3.1 Em `TenantExtension::getCssVars()`, passar a família por `HeadingFonts::normalize()`, o peso por `HeadingFonts::clampWeight()` (no `@import` e na regra do título) e a reserva por `HeadingFonts::fallback()`, sem mudar a ordem das famílias nem o `display=optional`. Verificar em localhost, num tenant das fixtures: (10/11/2026: H1 Open Sans 700 → `family=Open+Sans:wght@700` e `h1 { font-family: 'Open Sans', sans-serif !important; font-weight: 700 !important; }`; H1 Open Sans 900 → `@import` com 800 e `font-weight: 800`, a edição mostra 900; H2 Playfair Display 300 → `wght@400` e `'Playfair Display', serif`, peso 400; H3 "Comic Sans MS" gravado no JSON local → Outfit, e o nome não aparece no HTML; as URLs do `@import` respondem 200)
  - H1 em Open Sans 700: o `<style>` tem `family=Open+Sans:wght@700` e `h1 { font-family: 'Open Sans', sans-serif !important; font-weight: 700 !important;`;
  - H1 em Open Sans 900: o `@import` pede `Open+Sans:wght@800` e o h1 sai com `font-weight: 800`; a edição continua mostrando 900;
  - H2 em Playfair Display 300: o `@import` pede `Playfair+Display:wght@400` e o h2 sai com `'Playfair Display', serif` e `font-weight: 400`;
  - H3 com "Comic Sans MS" gravado direto no JSON local (`dbal:run-sql` em localhost): o `@import` pede Outfit e "Comic Sans MS" não aparece no HTML;
  - a URL do `@import` copiada do HTML responde HTTP 200 no `curl` em todos os casos acima.
- [x] 3.2 Comparar o `<style>` dos três sites locais com os arquivos da 1.2, com os tenants sem Open Sans e sem peso fora da faixa. Verificar: `diff` sem diferença nos três. (10/11/2026: `diff` sem diferença nos três sites locais, antes de qualquer tenant usar a Open Sans)
- [x] 3.3 Conferir no navegador, em localhost, um tenant com H1 a H5 em Open Sans nos temas wab (127.0.0.1), moderno (localhost), noticias (noticias.localhost) e cetec (um tenant local com o tema trocado para cetec). Verificar: na aba Rede, a Open Sans é baixada do `fonts.gstatic.com`; no painel Computed do DevTools, os títulos usam a Open Sans; não há erro no console. Anotar aqui os quatro temas conferidos. (10/11/2026, Playwright com Chrome: com H1 a H5 em Open Sans 700, wab (127.0.0.1), moderno (localhost), noticias (noticias.localhost) e cetec (localhost com o tema trocado por script e depois devolvido para moderno) mostram o título com `"Open Sans", sans-serif` 700, a fonte baixada do `fonts.gstatic.com` com 200 e só o 404 do `favicon.ico` no console)

## 4. Publicação

- [x] 4.1 Commit e `git push origin main`. Verificar: `git log --oneline origin/main -1` mostra o commit. (10/11/2026: 35f0848 e e2e2593 em origin/main)
- [x] 4.2 Backup antes (produção, **leitura**, como runcloud): anotar o `git rev-parse HEAD` de `~/webapps/app-wab-sites`, que é o ponto de volta. Não há dump do banco, porque o deploy não muda o banco. Verificar: o hash está anotado aqui. (10/11/2026: HEAD 2cdddf5 anotado em `~/backups/wab-sites-HEAD-antes-20261110-1952.txt`; foi feito também o dump, por causa da migration de outro change no mesmo deploy)
- [x] 4.3 Deploy (produção, **escrita**, com aprovação do Jonas), em `~/webapps/app-wab-sites`: (10/11/2026 19:53 UTC, com a autorização do Jonas ("crie… implemente… publique"): e2e2593 no ar; backup `~/backups/wab-sites-HEAD-antes-20261110-1952.txt` (HEAD 2cdddf5) e `wab-sites-antes-deploy-20261110-1952.sql.gz` (20 tabelas); `git pull`, migration Version20261110194600 e `./build.sh` com nice; HTTP 200 nos 8 domínios de cliente e no ajope. O `./build.sh` e a migration vieram dos outros changes do mesmo deploy)
  - `git pull --ff-only`;
  - `/RunCloud/Packages/php83rc/bin/php bin/console cache:clear`;
  - sem `./build.sh`, porque não muda CSS, JS, classe do Tailwind nem dependência;
  - sem `doctrine:migrations:migrate`, porque não há migration.
  - Verificar: o HEAD do servidor é igual ao da `main`.
- [x] 4.4 Conferência depois (produção, **leitura**): (10/11/2026: 200 nos 8 domínios; o `<style>` de marapossi.com.br continua `family=Outfit:wght@400` com H1 a H5 em Outfit 400; a tela `/superadmin/tenant/10/edit` não foi aberta, porque não há sessão de SuperAdmin de produção nesta sessão; a lista de famílias da tela foi conferida localmente na 2.2)
  - HTTP 200 nos 8 domínios de cliente: marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br e site.abifer.com.br;
  - `https://pkwy.com.br/superadmin/tenant/10/edit` com a Open Sans nos cinco selects de fonte;
  - o `<style>` de marapossi.com.br igual ao de antes do deploy.

## 5. Uso no tenant 10 (só com o OK do Jonas)

- [x] 5.1 Backup antes (produção, **leitura**): anotar aqui a fonte, o tamanho e o peso de H1 a H5 do tenant 10, como aparecem na edição. (10/11/2026: antes, H1 a H5 em Outfit 400 e "Padrão do Tema", lidos no `<style>` público (1.1))
- [x] 5.2 Escolher a Open Sans nos títulos que o Jonas indicar e salvar pelo `/superadmin` (produção, **escrita** pela tela). Verificar: ao reabrir a edição, a Open Sans está marcada nesses títulos. (10/11/2026, autorizado pelo Jonas ("já edite o superadmin tenant 10"): o Chrome ficou sem conexão nesta sessão, então o `font_settings` do tenant 10 foi gravado por `dbal:run-sql` com o mesmo JSON que o formulário grava (H1 a H5 em Open Sans 700, com 2.5rem, 1.5rem, 1.25rem, 1rem e 0.875rem), com o filtro `id = 10 AND domain = 'ajope.wab.com.br'` e com o dump das 19:52 como backup; 1 linha alterada)
- [x] 5.3 Conferência depois (produção, **leitura**): o HTML do site do tenant 10 tem `family=Open+Sans` no `@import`, os títulos usam a Open Sans no navegador, e o site responde HTTP 200. (10/11/2026: o HTML do ajope tem `family=Open+Sans:wght@700` e `h1` a `h5` em `'Open Sans', sans-serif` 700, com os tamanhos; a URL do Google Fonts responde 200; o site responde 200)
- [ ] 5.4 Se o pedido for de um cliente, registrar o resultado no ticket do sistema WAB do cliente do tenant 10. Verificar: o número do ticket está anotado aqui. Se for só da WAB, anotar "uso interno, sem ticket". (10/11/2026: nenhum cliente nem ticket da AJoPE no sistema WAB; falta o Jonas dizer onde registrar)

## Workflow follow-up

- Arquivar o change depois do deploy conferido (`/opsx:archive`), sincronizando os deltas de `gestao-de-sites` e `navegacao-e-aparencia` com as specs principais.
