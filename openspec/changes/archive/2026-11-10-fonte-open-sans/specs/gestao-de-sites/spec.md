# Spec Delta

## ADDED Requirements

### Requirement: Fontes dos títulos na edição do tenant
A edição do tenant no `/superadmin` SHALL oferecer, no campo de fonte de cada título de H1 a H5, as famílias Inter, Roboto, Playfair Display, Montserrat, Open Sans e Outfit, com a família gravada já marcada, ou Outfit quando não há nada gravado. Salvar SHALL gravar em `fontSettings` a família escolhida; uma família que não está nessa lista MUST ser gravada como Outfit.

#### Scenario: Escolher a Open Sans
- **WHEN** o SuperAdmin abre a edição de um tenant, escolhe Open Sans na fonte do H1 e salva
- **THEN** `fontSettings.h1.font` fica "Open Sans" e, ao reabrir a edição, o H1 aparece com a Open Sans marcada

#### Scenario: Lista das famílias
- **WHEN** o SuperAdmin abre a edição de qualquer tenant
- **THEN** cada um dos cinco campos de fonte, de H1 a H5, oferece as seis famílias: Inter, Roboto, Playfair Display, Montserrat, Open Sans e Outfit

#### Scenario: Família fora da lista
- **WHEN** a edição do tenant recebe um envio com uma família que não está na lista na fonte do H2
- **THEN** `fontSettings.h2.font` é gravado como Outfit
