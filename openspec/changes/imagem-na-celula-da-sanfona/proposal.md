# Proposal

## Why

A sanfona da Transmai (seção 97 de `transmai.wab.com.br`) reproduz a relação de revendas do site antigo, mas a coluna "Logo" ficou vazia: a célula da tabela só aceita texto. O site antigo mostra o logo de 28 das 49 revendas, e o cliente espera vê-los.

## What Changes

- Cada célula da tabela de um item da sanfona passa a aceitar **uma imagem** (logo), com ou sem texto junto. No painel, a célula ganha um botão para escolher a imagem, que mostra a miniatura e pode ser trocada ou removida.
- A imagem é enviada ao servidor **na hora em que é escolhida** (não no "Salvar"), por um endereço novo do painel que só aceita JPG, PNG, WebP ou GIF até 5 MB e grava em `uploads/page_block_media/`. Esse envio será reaproveitado por outros blocos (o "Mapa de representantes" usa para os logos).
- No site, a imagem sai como miniatura WebP de tamanho limitado, com texto alternativo; no celular, aparece no cartão da linha como os outros valores.
- Exportação e importação do site levam as imagens das células; a duplicação compartilha os arquivos.
- Linhas, colunas, colar de planilha e reordenação continuam funcionando com células que têm imagem; colar uma tabela não traz imagens (só texto).

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: requisitos novos para a imagem na célula da sanfona e para o envio de imagem do painel.
- `gestao-de-sites`: exportação e importação levam as imagens enviadas pelo painel para os blocos.

## Impact

- Código: `SanfonaDoBloco` (célula com imagem), rota nova de envio no `AdminContentController`, serviço novo `MidiaDoBloco` (gravação e lista de arquivos do `config`), `TenantExporter`, `TenantImporter`, `config/packages/liip_imagine.yaml` (filtro `logo_bloco`), `AquecerMiniaturasCommand`.
- Templates: `admin/block/_form_accordion.html.twig`; `themes/cetec/blocks/_accordion.html.twig` e `themes/atelie/blocks/_accordion.html.twig`.
- Sem migration. Produção: deploy com `git pull` e `./build.sh`; a pasta `public/uploads/page_block_media` é criada no primeiro envio.
