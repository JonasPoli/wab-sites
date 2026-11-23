# Spec Delta

## ADDED Requirements

### Requirement: Tabelas e sanfonas na versão Markdown
Na versão Markdown de uma página, tabelas do conteúdo SHALL sair como tabelas Markdown (cabeçalho, separador e uma linha por linha da tabela), e os itens de uma sanfona SHALL sair todos, abertos ou fechados no HTML, com o título do item seguido do conteúdo.

#### Scenario: Sanfona com tabela
- **WHEN** `/p/<slug>.md` é pedido para uma página com uma sanfona cujo item "Acre" tem uma tabela com cabeçalho "Cidade | Empresa"
- **THEN** o Markdown traz "Acre" e, abaixo, uma tabela com a linha `| Cidade | Empresa |` seguida do separador e das linhas de dados

#### Scenario: Item fechado
- **WHEN** a página tem uma sanfona com todos os itens fechados
- **THEN** o Markdown traz o conteúdo de todos os itens
