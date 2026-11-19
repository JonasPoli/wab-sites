# Spec Delta

## Purpose

Define a versão de cada página pública feita para assistentes e agentes de IA: o Markdown da página (endereço .md e negociação por Accept), o índice llms.txt e o llms-full.txt, e os cabeçalhos Link de descoberta, sempre restritos ao domínio (tenant) do pedido.

## ADDED Requirements

### Requirement: Versão Markdown das páginas
O sistema SHALL servir a versão Markdown de toda página pública HTML do tenant (home, páginas, categorias, notícias, posts do blog e autores) em `<caminho>.md` (a home em `/index.md`) e na própria URL quando o pedido preferir `text/markdown` no `Accept`. O Markdown SHALL trazer título, descrição e URL canônica no início, o conteúdo principal da página e os dados de contato do site, sem menus, scripts nem formulários.

#### Scenario: Endereço .md
- **WHEN** `/p/<slug>.md` de uma página publicada é pedido
- **THEN** a resposta é 200 em `text/markdown` com o título da página

#### Scenario: Negociação de conteúdo
- **WHEN** a home é pedida com `Accept: text/markdown`
- **THEN** a resposta é Markdown, com `Vary: Accept`
- **AND** com `Accept: text/html` ou `*/*` a resposta continua HTML

#### Scenario: Página que não existe
- **WHEN** `<caminho>.md` de uma página inexistente ou de outro tenant é pedido
- **THEN** a resposta é 404

### Requirement: Índice para agentes
O sistema SHALL servir por tenant `/llms.txt` (nome do site, resumo, lista das páginas públicas com o endereço Markdown, recursos para agentes e contato) e `/llms-full.txt` (o Markdown das páginas públicas num arquivo só), ambos em `text/plain`.

#### Scenario: Índice do domínio
- **WHEN** `/llms.txt` é aberto em dois tenants
- **THEN** cada um lista só as páginas do seu domínio, com URLs do próprio domínio

### Requirement: Cabeçalhos de descoberta
O sistema SHALL acrescentar às respostas HTML públicas do tenant os cabeçalhos `Link` para a versão Markdown (`alternate`), o `llms.txt` (`describedby`), o sitemap e o catálogo de API (`api-catalog`), e `Vary: Accept`.

#### Scenario: Home
- **WHEN** a home de um tenant é pedida
- **THEN** a resposta traz `Link` com `rel="api-catalog"` e `rel="alternate"; type="text/markdown"`
