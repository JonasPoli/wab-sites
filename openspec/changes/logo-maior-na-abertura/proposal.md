# Proposal

## Why

Alguns clientes querem que o logo apareça grande quando o site abre e diminua à medida que a página rola, até o tamanho normal do menu, como em mulheresnotributario.com.br e acervocomunicacao.com.br. Hoje o logo tem um tamanho fixo por tema (de 40 a 58 px de altura), e não há como mudar isso pelo painel.

## What Changes

- Na edição do tenant no `/superadmin`, junto do logo, um grupo novo "Logo na abertura" com:
  - a opção **"Logo maior na abertura da página"** (desligada por padrão);
  - a **altura do logo na abertura**, de 60 a 200 px (padrão 110 px), com prévia do logo nesse tamanho.
- Com a opção ligada, no topo da página o logo do cabeçalho aparece na altura escolhida, e o cabeçalho cresce para caber nele. Quando o visitante rola, o logo diminui aos poucos, acompanhando a rolagem, até o tamanho normal do tema, e volta a crescer quando ele sobe até o topo.
- No celular (menos de 768 px), o aumento é metade do escolhido, para o cabeçalho não ocupar a tela.
- Vale nos cinco temas (cetec, moderno, wab, noticias e atelie), só no logo do cabeçalho; o do rodapé não muda.
- Com a opção ligada, o logo é servido numa versão maior, para ficar nítido em telas de alta densidade.
- A escolha vai na exportação e na importação do tenant.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `navegacao-e-aparencia`: requisitos novos do logo maior na abertura (tamanho, redução com a rolagem, celular, cinco temas, nitidez).
- `gestao-de-sites`: a edição do tenant no `/superadmin` ganha o grupo "Logo na abertura", e a exportação e a importação levam a escolha.

## Impact

- Banco: coluna nova `tenant.header_logo` (JSON, nula = desligada), por migration gerada com `doctrine:migrations:diff`.
- Código: `src/Entity/Tenant.php`, `SuperAdminController::populateTenantFromRequest()`, `TenantExporter`, `TenantImporter`, `AquecerMiniaturasCommand` (miniatura nova do logo).
- Configuração: filtro novo `logo_site_grande` em `config/packages/liip_imagine.yaml`.
- Templates: parcial novo `themes/_comum/_logo_grande.html.twig` (CSS por tema + script da rolagem), incluído no `_header.html.twig` dos cinco temas; os temas que usam miniatura do logo (cetec, atelie e noticias) trocam para a versão maior com a opção ligada; `superadmin/tenant/edit.html.twig`.
- Produção: backup, `git pull`, `./build.sh` e `doctrine:migrations:migrate` (escrita; só com a confirmação do Jonas para esta mudança).
