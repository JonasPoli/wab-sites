## MODIFIED Requirements

### Requirement: Rotas antigas de notícias, vídeos e estudos
O sistema SHALL responder 404 em `/noticias/{slug}`, `/videos/{slug}` e `/estudos/{slug}`. Em `/noticias`, `/videos` e `/estudos`, SHALL renderizar com lista vazia `articles.html.twig`, `videos.html.twig` e `studies.html.twig` do tema quando o tema tem o template, e responder 404 "Página não encontrada." quando não tem.

#### Scenario: Vídeos num site com tema cetec
- **WHEN** um tenant com tema cetec ou moderno recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque só o tema wab tem `videos.html.twig` e `studies.html.twig`

#### Scenario: Vídeos no tema wab
- **WHEN** um tenant com tema wab recebe `/videos` ou `/estudos`
- **THEN** a página do tema é renderizada com a lista vazia

#### Scenario: Notícias no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias`
- **THEN** o `articles.html.twig` do cetec é renderizado com a lista vazia
