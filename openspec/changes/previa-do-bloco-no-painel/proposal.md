# Proposal

## Why

Quem monta uma página no `/admin` só vê como o bloco ficou depois de salvar e abrir o site. O Jonas pediu (18/11/2026) que a tela do bloco mostre, enquanto o formulário é preenchido, como ele vai ficar no tema do site, com o nome do campo no lugar do que ainda está vazio. A prévia precisa usar os próprios templates do tema, para acompanhar qualquer mudança neles sem manutenção separada.

## What Changes

- Prévia ao vivo na criação e na edição de qualquer bloco (`/admin/section/{id}/block/new?type=…` e `/admin/block/{id}/edit`), ao lado do formulário, em computador ou celular.
- A prévia é o `page.html.twig` do tema do site, renderizado com uma página e uma seção em memória (o mesmo fundo da seção real) contendo só o bloco, sem cabeçalho nem rodapé, num iframe. Nada é gravado.
- Campo de texto vazio aparece com o nome público do campo (o rótulo do formulário); imagem vazia aparece como uma imagem de exemplo; arquivo escolhido e ainda não salvo aparece na prévia pelo próprio arquivo do computador.
- Os temas ganham a variável `modoPrevia`, que esconde cabeçalho, barra superior, rodapé, botão do WhatsApp e Google Analytics; fora da prévia nada muda.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: requisitos novos da prévia do bloco no painel.

## Impact

- Código novo: `src/Service/PreviaDoBloco.php`, rota `POST /admin/block/previa` em `AdminContentController`, `templates/admin/block/_previa.html.twig`.
- Alterados: `templates/admin/block/new.html.twig` e `edit.html.twig` (formulário e prévia lado a lado); `base.html.twig` dos cinco temas (`modoPrevia`).
- Imagens de exemplo geradas uma vez em `public/uploads/*/previa-*.jpg|png` (fora do git, como os demais uploads).
- Sem banco novo, sem mudança no que é gravado.
