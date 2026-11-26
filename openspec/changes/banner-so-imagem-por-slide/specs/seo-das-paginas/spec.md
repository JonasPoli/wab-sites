# Spec Delta

## MODIFIED Requirements

### Requirement: Texto do banner só com a imagem na descrição
Quando a descrição da página vem do texto do conteúdo e o primeiro slide ativo de um banner está com "Só a imagem", esse banner SHALL contribuir só com a "Descrição da imagem" desse slide; título, subtítulo e botões guardados dele, que não aparecem na página, MUST NOT entrar na descrição. Sem descrição da imagem, o banner não contribui e vale o bloco seguinte. Banner cujo primeiro slide ativo tem texto contribui com o texto, como os demais blocos.

#### Scenario: Banner com descrição da imagem
- **WHEN** a página e o site estão sem descrição de SEO e o primeiro bloco é um banner cujo primeiro slide ativo é "só a imagem", com subtítulo guardado "Há 26 anos valorizando a saúde" e descrição da imagem "Campanha Novembro Azul: cuidar de você também é prevenção"
- **THEN** a `<meta name="description">` começa por "Campanha Novembro Azul: cuidar de você também é prevenção" e não contém "Há 26 anos"

#### Scenario: Banner sem descrição da imagem
- **WHEN** o primeiro slide ativo do banner é "só a imagem", sem descrição da imagem, e o bloco seguinte é um texto
- **THEN** a descrição vem desse texto

#### Scenario: Primeiro slide com texto
- **WHEN** o primeiro slide ativo do banner tem texto e o segundo é "só a imagem"
- **THEN** a descrição começa pelo subtítulo do primeiro slide
