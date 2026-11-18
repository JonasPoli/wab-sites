# Design

## Decisions

- **Renderização pelo tema, em iframe.** `POST /admin/block/previa?section=<id>` (ou `?block=<id>` na edição) monta em memória `Page` (slug `previa`, sem título) → `PageSection` (cópia dos campos de fundo da seção real, sem título) → `PageBlock` com os dados do formulário, e renderiza `themes/<tema>/page.html.twig` com `modoPrevia: true`. O resultado vai para o `srcdoc` de um iframe no painel. Como é o template do tema, qualquer mudança nele aparece na prévia. Alternativa descartada: um template de prévia por tema (duplicaria os parciais).
- **`modoPrevia` nos `base.html.twig`.** Esconde cabeçalho, barra superior, rodapé, WhatsApp e GA; sem a variável, a saída não muda (conferido com `sh/regressao.sh`).
- **Nada é persistido.** `App\Service\PreviaDoBloco` cria objetos novos (nunca altera a entidade gerenciada) e não chama `flush`. Na edição, copia do bloco salvo imagem, configuração e filhos (fotos, depoimentos, logos, membros) e aplica por cima o que veio do formulário.
- **Campos vazios.** O JavaScript do painel monta o `FormData` do formulário e, para cada campo de texto vazio, envia o rótulo do campo (`label` do `.af-field`, `aria-label` ou `placeholder`; nos grupos repetidos, "<cabeçalho do grupo> — <rótulo>"). O conteúdo do editor Quill é copiado para o textarea antes.
- **Imagens.** O servidor usa arquivos de exemplo gerados com GD (`previa-<tipo>.jpg|png`, com o nome do campo desenhado). Para arquivo escolhido e não salvo, usa `previa-<tipo>-arquivo.*`; o JavaScript troca essas imagens (e o `srcset`/`<source>`) pelo `URL.createObjectURL` do arquivo, na ordem dos campos de arquivo. Os arquivos não sobem antes de salvar.
- **Isolamento.** A seção e o bloco são buscados por `sectionOr404()`/`blockOr404()`, que só acham conteúdo de página ou categoria do site do domínio; a rota está sob `/admin` (TENANT_ACCESS) e na classe com `ROLE_ADMIN`.
- **No iframe**, links e formulários são desativados (cliques não navegam), e a altura acompanha o conteúdo. Botões "Computador" (1280 px, reduzido para caber) e "Celular" (390 px).

## Risks / Trade-offs

- [Parcial que depende de id do bloco] → os ids ficam nulos na prévia; os parciais usam o id só em `id=` e seletores do próprio bloco, que continuam únicos por haver um bloco só.
- [Prévia com muitas fotos gera miniaturas] → só as imagens de exemplo e as já salvas, que já têm miniatura.
