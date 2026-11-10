## ADDED Requirements

### Requirement: Cabeçalho da notícia no tema noticias
No tema `noticias`, a partir de 768 px de largura, a página da notícia SHALL usar a Open Sans e: título com o tamanho e o peso do H1 do tenant, entrelinha 1,2 e espaço entre letras de −0,045 em; linha fina com 15 px, peso 400 e entrelinha de 22 px; autor com 14 px e peso 600; data com 12 px; legenda da foto com 12 px; coluna com até 648 px. As cores SHALL continuar as do tema.

#### Scenario: Notícia do portal da AJoPE no computador
- **WHEN** o tenant tem H1 em Open Sans, 2.5rem e peso 700 e alguém abre uma notícia a 1280 px
- **THEN** o título sai com 40 px e peso 700, a linha fina com 15 px e o autor com 14 px e peso 600

### Requirement: Texto da notícia no tema noticias
No tema `noticias`, a partir de 768 px, o texto da notícia SHALL ter 18 px, entrelinha de 28 px, espaço entre letras de −0,015 em e 32 px entre parágrafos; o intertítulo (h2 do texto) SHALL ter o tamanho e o peso do H2 do tenant e entrelinha de 28 px; os itens de lista, 20 px e entrelinha de 28 px; a citação, 20 px, peso 700, entrelinha de 28 px e sem itálico; os links, peso 700 e sem sublinhado.

#### Scenario: Texto no computador
- **WHEN** alguém abre uma notícia a 1280 px
- **THEN** os parágrafos saem com 18 px e entrelinha de 28 px, separados por 32 px

#### Scenario: Citação no texto
- **WHEN** a notícia tem uma citação
- **THEN** a citação sai em negrito, sem itálico, com 20 px

### Requirement: Tipografia da lista de notícias no tema noticias
No tema `noticias`, na home, em `/noticias`, na página da editoria e na página do autor, cada item SHALL ter, a partir de 768 px: chapéu com 16 px e peso 600; título com o tamanho e o peso do H2 do tenant, entrelinha 1,1 e espaço entre letras de −0,035 em, igual na primeira notícia e nas outras; linha fina com 16 px e entrelinha de 24 px; links relacionados com 14 px; data e editoria com 12 px e entrelinha de 18 px.

#### Scenario: Home do portal no computador
- **WHEN** o tenant tem H2 em Open Sans, 1.5rem e peso 700 e alguém abre a home a 1280 px
- **THEN** os títulos da lista saem com 24 px e peso 700, os chapéus com 16 px e peso 600 e as datas com 12 px

### Requirement: Títulos nos quadros laterais do tema noticias
No tema `noticias`, os títulos das notícias dentro dos quadros "Leia também" e "Mais lidas" SHALL ter 16 px e peso 700.

#### Scenario: Quadro Mais lidas
- **WHEN** a home mostra o quadro "Mais lidas"
- **THEN** os títulos das notícias do quadro saem com 16 px e peso 700

### Requirement: Títulos do tema noticias no celular
Abaixo de 768 px de largura, no tema `noticias`, o título da página (h1) SHALL ter 70% do tamanho do H1 do tenant, e o título dos itens da lista, 83,3% do tamanho do H2. O intertítulo do texto SHALL manter o tamanho do H2. Os itens de lista do texto e as citações SHALL ter 18 px.

#### Scenario: Notícia no celular
- **WHEN** o tenant tem H1 com 2.5rem e alguém abre uma notícia a 390 px
- **THEN** o título sai com 28 px

#### Scenario: Lista no celular
- **WHEN** o tenant tem H2 com 1.5rem e alguém abre a home a 390 px
- **THEN** os títulos da lista saem com 20 px

### Requirement: Tamanho padrão dos títulos no tema noticias
No tema `noticias`, quando o tenant não tem tamanho gravado para H1, H2 ou H3 ("Padrão do Tema"), o tema SHALL usar 40 px, 24 px e 20 px, respectivamente, nas regras de tipografia acima. Um tamanho gravado que não seja um número seguido de `px`, `rem` ou `em` SHALL ser tratado como não gravado.

#### Scenario: Portal sem tamanhos gravados
- **WHEN** o tenant do tema `noticias` tem "Padrão do Tema" no tamanho de H1 e H2
- **THEN** a notícia sai com o título em 40 px a 1280 px e em 28 px a 390 px, e a lista com títulos de 24 px

### Requirement: Rótulos dos quadros laterais fora da tipografia do H2
No tema `noticias`, os cabeçalhos dos quadros "Leia também", "Mais lidas" e de páginas da editoria SHALL manter 14 px e peso 600, qualquer que seja o tamanho ou o peso do H2 gravado no tenant.

#### Scenario: H2 grande no tenant
- **WHEN** o tenant tem H2 com 1.5rem e peso 700
- **THEN** o cabeçalho "Mais lidas" continua com 14 px e peso 600
