# Tasks

## 1. Servidor

- [x] 1.1 `src/Service/PreviaDoBloco.php`: montar Page/PageSection/PageBlock em memória a partir do pedido (texto, configuração, fotos, depoimentos, logos, membros, slides), itens de exemplo nas listas vazias e as imagens de exemplo geradas com GD; verificar com teste de unidade de que nada é persistido
- [x] 1.2 Rota `POST /admin/block/previa` em `AdminContentController` (seção ou bloco do site do domínio; 404 para outro site), renderizando `themes/<tema>/page.html.twig` com `modoPrevia`; verificar com teste funcional (200 com o texto enviado; 404 para seção de outro site)
- [x] 1.3 `modoPrevia` nos `base.html.twig` de cetec, moderno, wab, noticias e atelie; verificar com `sh/regressao.sh` sem diferença

## 2. Painel

- [x] 2.1 `templates/admin/block/_previa.html.twig` (iframe, Computador/Celular, atualização com espera de 500 ms, rótulos nos campos vazios, troca dos arquivos escolhidos, cliques desativados, altura automática) incluído em `new.html.twig` e `edit.html.twig`
- [x] 2.2 Conferir no local a prévia dos 23 tipos de bloco nos temas cetec e atelie (novo e edição), sem erro

## 3. Publicação

- [ ] 3.1 Testes, regressão, commit, deploy (`git pull` + `cache:clear`) e conferência no painel de produção
