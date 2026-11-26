# Spec Delta

## ADDED Requirements

### Requirement: Texto do banner só com a imagem na descrição
Quando a descrição da página vem do texto do conteúdo, um banner com "Só a imagem" ligada SHALL contribuir só com a "Descrição da imagem" do seu primeiro slide ativo; título, subtítulo e botões guardados desse banner, que não aparecem na página, MUST NOT entrar na descrição. Sem descrição da imagem, o banner não contribui e vale o bloco seguinte.

#### Scenario: Banner com descrição da imagem
- **WHEN** a página e o site estão sem descrição de SEO e o primeiro bloco é um banner "só a imagem" com subtítulo guardado "Há 26 anos valorizando a saúde" e descrição da imagem "Campanha Novembro Azul: cuidar de você também é prevenção"
- **THEN** a `<meta name="description">` começa por "Campanha Novembro Azul: cuidar de você também é prevenção" e não contém "Há 26 anos"

#### Scenario: Banner sem descrição da imagem
- **WHEN** o banner "só a imagem" não tem descrição da imagem e o bloco seguinte é um texto
- **THEN** a descrição vem desse texto
