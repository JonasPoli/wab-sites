# Spec Delta

## MODIFIED Requirements

### Requirement: Sitemap
O sistema SHALL responder em `/sitemap.xml` um XML com o cabeçalho `X-Robots-Tag: noindex` e, no domínio aberto: a home (prioridade 1.0), uma URL `/p/{slug}` por página do tenant com slug e com data de publicação no passado, menos a página usada como home (prioridade 0.8), e uma URL `/categoria/{slug}` por categoria do tenant que tem conteúdo publicado (prioridade 0.6). No tema `noticias`, o XML SHALL trazer também uma URL por notícia visível que não é de exemplo (prioridade 0.7), uma por editoria com notícia visível e uma por autor aprovado com notícia visível (prioridade 0.5). Todo `lastmod` SHALL ser a data e hora reais da última alteração daquele conteúdo, em ISO 8601 com fuso; o da home SHALL ser a alteração mais recente entre as URLs listadas. Página com publicação futura, página de busca, cadastro de jornalista, login e painéis MUST NOT entrar.

#### Scenario: Sitemap do site
- **WHEN** um robô lê `https://pkwy.com.br/sitemap.xml`
- **THEN** recebe a home, as páginas publicadas e as categorias com conteúdo do tenant 3, com endereços no domínio pkwy.com.br

#### Scenario: Portal com rascunho
- **WHEN** um site com tema `noticias` tem 3 notícias publicadas e 1 em rascunho
- **THEN** o sitemap traz as 3 publicadas em `/noticias/{slug}` e não traz o rascunho

#### Scenario: Site institucional
- **WHEN** um site com tema cetec recebe `/sitemap.xml`
- **THEN** o XML traz a home, as páginas e as categorias, sem URLs de notícia, de editoria nem de autor

#### Scenario: Página que serve de home
- **WHEN** o tenant tem `homePage` definida
- **THEN** essa página aparece só como `https://<domínio>/`, e não também como `/p/{slug}`

#### Scenario: Página em rascunho
- **WHEN** o tenant tem uma página com slug ainda não publicada
- **THEN** ela não aparece no sitemap

#### Scenario: Data de alteração
- **WHEN** uma página foi alterada às 14h32 de 10/11/2026 no horário de Brasília
- **THEN** o `lastmod` dela traz essa data e hora com o fuso, e não a data de hoje

#### Scenario: Portal só com notícias de exemplo
- **WHEN** um site do tema `noticias` tem apenas notícias de exemplo
- **THEN** o sitemap traz a home e as páginas, e nenhuma URL de notícia, de editoria nem de autor
