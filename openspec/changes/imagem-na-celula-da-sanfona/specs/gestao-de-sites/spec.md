# Spec Delta

## ADDED Requirements

### Requirement: Imagens enviadas pelo painel na exportação
A exportação do site SHALL levar as imagens enviadas pelo painel e citadas na configuração dos blocos (como as imagens das células da sanfona), e a importação SHALL recolocá-las; um ZIP sem essas imagens SHALL ser importado sem erro.

#### Scenario: Ida e volta de uma sanfona com logos
- **WHEN** um site com uma sanfona de tabelas com logos é exportado e importado em outro domínio
- **THEN** o site importado mostra os mesmos logos nas mesmas células

#### Scenario: ZIP antigo
- **WHEN** um ZIP exportado antes desta mudança é importado
- **THEN** a importação termina sem erro
