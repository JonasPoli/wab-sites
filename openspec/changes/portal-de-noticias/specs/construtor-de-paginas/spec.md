## MODIFIED Requirements

### Requirement: Rotas antigas de notícias, vídeos e estudos
O sistema SHALL responder 404 em `/videos/{slug}` e `/estudos/{slug}`, e em `/noticias/{slug}` quando o tema do site não é `noticias`. Em `/videos`, `/estudos` e, fora do tema `noticias`, em `/noticias`, SHALL renderizar com lista vazia `videos.html.twig`, `studies.html.twig` e `articles.html.twig` do tema quando o tema tem o template, e responder 404 "Página não encontrada." quando não tem. No tema `noticias`, `/noticias` e `/noticias/{slug}` mostram as notícias do site (capability `noticias`).

#### Scenario: Vídeos num site com tema cetec
- **WHEN** um tenant com tema cetec ou moderno recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque só o tema wab tem `videos.html.twig` e `studies.html.twig`

#### Scenario: Vídeos no tema wab
- **WHEN** um tenant com tema wab recebe `/videos` ou `/estudos`
- **THEN** a página do tema é renderizada com a lista vazia

#### Scenario: Notícias no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias`
- **THEN** o `articles.html.twig` do cetec é renderizado com a lista vazia

#### Scenario: Notícia no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias/{slug}`, mesmo com uma notícia publicada com esse slug no banco
- **THEN** a resposta é 404

#### Scenario: Vídeos no tema noticias
- **WHEN** um tenant com tema `noticias` recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque o tema `noticias` não tem esses templates

### Requirement: Sitemap
O sistema SHALL responder em `/sitemap.xml` um XML com a home (prioridade 1.0, `lastmod` do dia) e uma URL `/p/{slug}` por página do tenant com slug (prioridade 0.8, `lastmod` do `updatedAt`), com o cabeçalho `X-Robots-Tag: noindex`; categorias não entram. No tema `noticias`, o XML SHALL trazer também uma URL `/noticias/{slug}` por notícia visível (prioridade 0.7, `lastmod` da última edição).

#### Scenario: Sitemap do site
- **WHEN** um robô lê `https://pkwy.com.br/sitemap.xml`
- **THEN** recebe a home e as páginas do tenant 3, com endereços no domínio pkwy.com.br

#### Scenario: Portal com rascunho
- **WHEN** um site com tema `noticias` tem 3 notícias publicadas e 1 em rascunho
- **THEN** o sitemap traz as 3 publicadas em `/noticias/{slug}` e não traz o rascunho

#### Scenario: Site institucional
- **WHEN** um site com tema cetec recebe `/sitemap.xml`
- **THEN** o XML traz só a home e as páginas, sem URLs de notícia
