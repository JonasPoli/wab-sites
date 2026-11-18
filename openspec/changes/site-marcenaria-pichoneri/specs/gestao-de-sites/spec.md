# Spec Delta

## ADDED Requirements

### Requirement: Redirecionamentos na exportação, na importação e na exclusão
A exportação do site SHALL levar os redirecionamentos, a importação SHALL recriá-los (um ZIP sem essa lista importa sem redirecionamentos), e a exclusão do site SHALL apagá-los.

#### Scenario: Ida e volta
- **WHEN** um site com 55 redirecionamentos é exportado e importado em outro domínio
- **THEN** o site importado tem os mesmos 55 redirecionamentos

#### Scenario: Excluir o site
- **WHEN** o SuperAdmin exclui um site com redirecionamentos
- **THEN** nenhum redirecionamento dele permanece no banco
