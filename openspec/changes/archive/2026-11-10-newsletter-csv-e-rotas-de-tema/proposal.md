# Proposta: newsletter-csv-e-rotas-de-tema

## Why

Dois erros 500 com causa conhecida, para corrigir conforme decidido pelo Jonas em 09/11/2026. A exportação `/admin/newsletter/export.csv` quebra sempre que o site tem inscritos, porque chama `getCreatedAt()`, que `NewsletterSubscriber` não tem; em produção são 6 inscritos (3 no tenant 1 e 3 no tenant 3). E `/videos` e `/estudos` respondem 500 nos 8 domínios de cliente (conferido em 09/11/2026), porque só o tema wab tem `videos.html.twig` e `studies.html.twig`, e todos os tenants reais usam o cetec.

## What Changes

- A exportação da newsletter passa a usar a data de inscrição (`subscribedAt`), com o mesmo cabeçalho `id,email,data`. As células passam a ser escapadas como CSV, e a célula que começa com `=`, `+`, `-` ou `@` recebe um apóstrofo antes, para a planilha não executar como fórmula.
- O botão "Exportar CSV" do `/admin/newsletter` passa a baixar o arquivo da rota. Hoje ele monta o CSV no navegador a partir da tabela: o nome vem como "—", a data vem sem hora e as aspas não são escapadas.
- `/videos` e `/estudos` passam a responder 404 "Página não encontrada." no tema que não tem o template (cetec e moderno) e continuam renderizando a lista vazia no tema wab. As rotas ficam, porque o tema wab chama `path('pub_videos')`, `path('pub_studies')` e as rotas de detalhe no rodapé, na home e nas próprias páginas.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `contato-e-newsletter`: a "Exportação da newsletter" passa a gerar o CSV com a data de inscrição, escapado, e o botão do painel passa a usá-la.
- `construtor-de-paginas`: as "Rotas antigas de notícias, vídeos e estudos" passam a responder 404 nos temas sem o template, em vez de erro.

## Impact

- Código: `AdminContentController::newsletterExport()`, `templates/admin/newsletter/index.html.twig` (botão e remoção do JavaScript de exportação) e `WabPublicController` (`articles()`, `videos()`, `studies()` e um helper para template de tema).
- Nenhum template público criado ou alterado; sem migration.
- Produção: deploy com `git pull` e `./build.sh`, porque muda um template do painel; nenhuma escrita no banco.
- Independe de `isolamento-entre-sites`; as duas mexem em métodos diferentes de `AdminContentController` e `WabPublicController`.
