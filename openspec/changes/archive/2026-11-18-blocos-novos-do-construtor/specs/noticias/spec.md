# Spec Delta

## MODIFIED Requirements

### Requirement: Páginas institucionais no tema noticias
No tema `noticias`, `/p/{slug}`, a home definida em `homePage`, `/membro/{slug}` e as seções de categoria SHALL renderizar com o cabeçalho e o rodapé do tema `noticias` e com todos os tipos de bloco na aparência do tema cetec, sem erro.

#### Scenario: Página "Sobre a AJoPE"
- **WHEN** o site com tema `noticias` tem a página `sobre` com seções de texto, equipe e contato
- **THEN** `/p/sobre` responde 200 com os três blocos entre o cabeçalho e o rodapé do portal

#### Scenario: Bloco novo no portal
- **WHEN** uma página do portal tem um bloco `cta`
- **THEN** a faixa aparece entre o cabeçalho e o rodapé do portal, na cor primária do site
