# Tasks

## 1. Servidor

- [x] 1.1 `src/Service/PreviaDoBloco.php`: montar Page/PageSection/PageBlock em memória a partir do pedido (texto, configuração, fotos, depoimentos, logos, membros, slides), itens de exemplo nas listas vazias e as imagens de exemplo geradas com GD; verificar com teste de unidade de que nada é persistido
- [x] 1.2 Rota `POST /admin/block/previa` em `AdminContentController` (seção ou bloco do site do domínio; 404 para outro site), renderizando `themes/<tema>/page.html.twig` com `modoPrevia`; verificar com teste funcional (200 com o texto enviado; 404 para seção de outro site)
- [x] 1.3 `modoPrevia` nos `base.html.twig` de cetec, moderno, wab, noticias e atelie; verificar com `sh/regressao.sh` sem diferença

## 2. Painel

- [x] 2.1 `templates/admin/block/_previa.html.twig` (iframe, Computador/Celular, atualização com espera de 500 ms, rótulos nos campos vazios, troca dos arquivos escolhidos, cliques desativados, altura automática) incluído em `new.html.twig` e `edit.html.twig`
- [x] 2.2 Conferir no local a prévia dos 23 tipos de bloco nos temas cetec e atelie (novo e edição), sem erro

## 3. Publicação

- [x] 3.1 Testes, regressão, commit, deploy (`git pull` + `cache:clear`) e conferência no painel de produção

## Conferência

- Local: os 23 tipos abrem a tela de criação com a prévia e a prévia responde 200 sem erro nos temas atelie e cetec; edição de um portfólio com as fotos salvas; seção de outro site responde 404; sem cabeçalho, rodapé nem WhatsApp na prévia; `tests/Controller/PreviaDoBlocoTest.php` passa (texto enviado, parcial do tema, depoimento de exemplo, 404 e nenhum bloco gravado).
- `sh/regressao.sh`: sem diferença causada pela mudança (só as já conhecidas do blog e uma notícia agendada das fixtures que passou a aparecer pelo horário).
- Produção (Chrome, marapossi.com.br, sem salvar): prévia ao lado do formulário no tema cetec com os nomes dos campos vazios; título e item digitados aparecem em segundos; edição do bloco 9 com o conteúdo real; Ampliar (largura total) e Celular funcionando.
- Acréscimo: botão "Ampliar", porque no modo Computador a prévia lado a lado fica pequena numa tela de notebook.
