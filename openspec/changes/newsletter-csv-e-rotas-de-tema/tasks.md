# Tasks: newsletter-csv-e-rotas-de-tema

Conferência em `http://127.0.0.1:8000` (tenant A das fixtures, tema wab) e `http://localhost:8000` (tenant B, tema moderno). Os testes de `tests/` estão quebrados e não contam como conferência.

## 1. Exportação da newsletter

- [x] 1.1 Carregar as fixtures (`bin/console doctrine:fixtures:load --no-interaction`), ligar a newsletter dos tenants A e B pelo `/superadmin` e inscrever por POST direto em `/newsletter/subscribe` dois e-mails no tenant B (`ana@exemplo.com` e `=teste@exemplo.com`) e um no tenant A. Conferir que o `/admin/newsletter` de cada host lista os seus. (10/11/2026, porta 8101, banco local separado: newsletter já ligada nos tenants A e B (salva de novo pelo /superadmin); inscritos por POST `ana@exemplo.com` e `=teste@exemplo.com` no B e `joao@exemplo.com` no A; cada `/admin/newsletter` lista só os seus)
- [x] 1.2 Reescrever `newsletterExport()` com `fputcsv` em `php://temp`, `getSubscribedAt()->format('Y-m-d H:i')` e apóstrofo antes de célula que começa com `=`, `+`, `-` ou `@`. Conferir: `localhost:8000/admin/newsletter/export.csv` responde 200 com `id,email,data` e as duas linhas do tenant B, com a data de inscrição e a célula `'=teste@exemplo.com`; o arquivo de `127.0.0.1:8000` traz só o inscrito do tenant A; sem inscritos, vem só o cabeçalho. (10/11/2026: localhost respondeu 200 com `id,email,data` e as duas linhas do B, com a data de inscrição e a célula `'=teste@exemplo.com`; 127.0.0.1 trouxe só o inscrito do A; noticias.localhost, sem inscritos, só o cabeçalho)
- [x] 1.3 Trocar o botão "Exportar CSV" de `templates/admin/newsletter/index.html.twig` por link para `admin_newsletter_export` e remover a função `exportCSV()`. Conferir que o clique baixa o mesmo arquivo de 1.2 e que o console do navegador não mostra erro. (10/11/2026: o botão é um link para `/admin/newsletter/export.csv` (a mesma rota de 1.2) e a função `exportCSV()` saiu do HTML; no Chrome, o `/admin/newsletter` carregou sem mensagem no console. O download pelo clique não foi feito no Chrome do Jonas)

## 2. Rotas de vídeos e estudos

- [x] 2.1 Criar no `WabPublicController` o helper que responde 404 "Página não encontrada." quando o template não existe no tema e usá-lo em `articles()`, `videos()` e `studies()`. Conferir: em localhost:8000 (moderno), `/videos` e `/estudos` respondem 404 e `/noticias` 200; em 127.0.0.1:8000 (wab), `/videos`, `/estudos`, `/noticias` e `/` respondem 200, com o rodapé do wab montando os links de vídeos e estudos. (10/11/2026: o helper `renderThemeOr404()` já veio no commit do portal-de-noticias (f71e18f), usado em `articles()` (fora do tema noticias), `videos()` e `studies()`; localhost (moderno): `/videos` e `/estudos` 404 "Página não encontrada." e `/noticias` 200; 127.0.0.1 (wab): `/videos`, `/estudos`, `/noticias` e `/` 200, com os links `/videos` e `/estudos` no rodapé)
- [x] 2.2 Trocar o tema do tenant B para cetec em `/admin/settings` de localhost:8000 e conferir `/videos` e `/estudos` com 404 e `/noticias` e `/` com 200; voltar o tema para moderno. (10/11/2026: tenant B no cetec pelo `/admin/settings`: `/videos` e `/estudos` 404, `/noticias` e `/` 200; tema de volta para moderno, com a home mantida)

## 3. Produção

- [ ] 3.1 Backup (leitura, antes da escrita): anotar `git rev-parse HEAD` do webapp para a volta. O deploy não grava no banco.
- [ ] 3.2 Deploy (escrita, com aprovação do Jonas): `git push origin main` local; no servidor, como runcloud, `git pull` e `./build.sh` (mudou template do painel); sem migration. Conferir HTTP 200 em `/` dos 8 domínios de cliente (marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br).
- [ ] 3.3 Conferência (leitura): `/videos` e `/estudos` com 404 e `/noticias` com 200 nos 8 domínios; o Jonas baixa o CSV em `marapossi.com.br/admin/newsletter` e `pkwy.com.br/admin/newsletter` e confere 3 linhas em cada, sem colar o conteúdo em lugar nenhum.

## Workflow follow-up

- Não é pedido de cliente: não há ticket obrigatório; se o Jonas quiser registrar, ticket interno no cliente WAB 196.
- Arquivar a mudança depois da conferência de produção.
