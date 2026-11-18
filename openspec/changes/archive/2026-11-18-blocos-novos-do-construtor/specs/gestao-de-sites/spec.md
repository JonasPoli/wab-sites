# Spec Delta

## ADDED Requirements

### Requirement: Blocos novos na exportação e na importação
A exportação do site SHALL levar, além do que já leva, o título e a categoria de cada foto de bloco e o arquivo de vídeo do bloco `video`; a importação SHALL recriá-los, e SHALL aceitar um ZIP exportado antes desta mudança tratando esses dados como vazios.

#### Scenario: Ida e volta de um portfólio
- **WHEN** um site com um bloco `portfolio` de 5 fotos categorizadas e um bloco `video` com MP4 é exportado e importado em outro domínio
- **THEN** o site importado mostra o mesmo filtro, as mesmas fotos e o mesmo vídeo

#### Scenario: ZIP antigo
- **WHEN** um ZIP exportado antes desta mudança é importado
- **THEN** a importação termina sem erro e as fotos de galeria ficam sem título e sem categoria
