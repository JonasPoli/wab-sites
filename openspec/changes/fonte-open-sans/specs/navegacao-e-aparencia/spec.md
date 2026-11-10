# Spec Delta

## MODIFIED Requirements

### Requirement: Variáveis de cor e fontes
A função `tenant_css_vars()` SHALL gerar um `<style>` com `--color-primary`, `--color-secondary` e as versões RGB a partir das cores do tenant, trocadas pelas versões escuras em `[data-theme="dark"]`, `.dark`, `.wab-section-dark` e `.mp-section-dark`, mais o `@import` do Google Fonts com as famílias e os pesos usados e as regras de fonte, peso e tamanho de h1 a h5 de `fontSettings`, com a reserva `serif` na Playfair Display e `sans-serif` nas demais.

#### Scenario: Barra superior ligada
- **WHEN** `navigationSettings.topBarEnabled` está ligado
- **THEN** `--topbar-height` vale 40px; desligado, vale 0px

#### Scenario: Título em Open Sans
- **WHEN** o tenant tem `fontSettings.h1` com a família "Open Sans" e o peso 700
- **THEN** o `@import` do Google Fonts pede a Open Sans com o peso 700, e o h1 do site sai com `font-family: 'Open Sans', sans-serif` e `font-weight: 700`

## ADDED Requirements

### Requirement: Peso dos títulos dentro da faixa da família
O peso de cada título SHALL ficar dentro da faixa que a família tem no Google Fonts: de 300 a 800 na Open Sans, de 400 a 900 na Playfair Display e de 100 a 900 nas demais. Um peso gravado fora da faixa SHALL ser trocado pelo mais próximo dentro dela, no `@import` e na regra do título, sem mudar o valor gravado em `fontSettings`.

#### Scenario: Open Sans com peso 900
- **WHEN** o tenant tem `fontSettings.h1` com a família "Open Sans" e o peso 900
- **THEN** o `@import` pede a Open Sans com o peso 800, o h1 sai com `font-weight: 800` e a edição do tenant continua mostrando o peso 900

#### Scenario: Playfair Display com peso 300
- **WHEN** o tenant tem `fontSettings.h2` com a família "Playfair Display" e o peso 300
- **THEN** o `@import` pede a Playfair Display com o peso 400 e o h2 sai com `font-weight: 400`

### Requirement: Família dos títulos fora da lista
Uma família gravada em `fontSettings` que não está entre as oferecidas na edição do tenant SHALL ser tratada como Outfit no `@import` e na regra do título, e o texto gravado MUST NOT entrar no CSS da página.

#### Scenario: Família desconhecida vinda de importação
- **WHEN** um tenant importado de um ZIP tem `fontSettings.h3` com a família "Comic Sans MS"
- **THEN** o `@import` pede a Outfit, o h3 sai com `font-family: 'Outfit', sans-serif` e o nome "Comic Sans MS" não aparece no HTML da página
