# Proposal

## Why

Sites de clientes precisam mostrar listas longas organizadas em grupos que abrem e fecham, como a relação de revendas por estado em https://www.transmai.com.br/revenda/ (um item por estado, cada um com uma tabela de cidade, empresa, responsáveis etc.). Nenhum dos 23 tipos de bloco atuais faz isso: o "Apenas Texto" não abre e fecha e o editor de texto do painel não cria tabelas.

## What Changes

- Tipo de bloco novo `accordion` ("Sanfona"), oferecido no `/admin` de qualquer site:
  - cabeçalho padrão (pré-título, título e texto, todos opcionais);
  - lista de itens com título; cada item é **texto formatado** (editor de texto rico, o mesmo das notícias) **ou tabela** (linhas e colunas livres, primeira linha como cabeçalho opcional);
  - opções: abrir o primeiro item ao carregar e permitir vários itens abertos ao mesmo tempo (padrão: um de cada vez);
  - no painel, itens podem ser acrescentados, removidos e reordenados; a tabela ganha e perde linhas e colunas e aceita colar um trecho copiado do Excel, do Google Planilhas ou de uma tabela de página web.
- Desenho no site: abre e fecha sem recarregar e funciona sem JavaScript; tabela com rolagem lateral, e no celular, quando há cabeçalho, cada linha vira um cartão com "coluna: valor"; e-mails e endereços web nas células viram links.
- Visual próprio adequado em todos os temas: cetec (com modo escuro e seções de fundo escuro), moderno, wab e noticias (desenho do cetec, nas cores do site) e atelie (desenho próprio, no estilo do tema).
- HTML do texto dos itens é sanitizado ao gravar (sem script, estilo ou atributos de evento).
- Prévia do bloco no painel mostra a sanfona com itens de exemplo enquanto não há item.
- Versão Markdown da página (agentes) traz todos os itens abertos e as tabelas como tabelas Markdown.
- Menu do Modo Landing Page: seção sem título cujo primeiro bloco é uma sanfona recebe o rótulo "Informações".

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: "Blocos por tipo" passa de 23 para 24 tipos; requisitos novos para a sanfona (itens de texto e de tabela, abrir e fechar, tabela no celular, edição no painel, colar de planilha, sanitização, prévia).
- `navegacao-e-aparencia`: "Menu do Modo Landing Page" ganha o rótulo da sanfona (e passa a citar o de `blog_posts`, que o código já usa).
- `conteudo-para-agentes`: a versão Markdown da página converte tabelas em tabelas Markdown.

## Impact

- Código: `src/Entity/Enum/BlockType.php`, `src/Controller/admin/AdminContentController.php` (`populateBlock`), `src/Service/PreviaDoBloco.php`, serviço novo de normalização dos itens, filtro Twig novo para as células, `src/Agentes/MarkdownDaPagina.php`.
- Templates: `admin/block/new.html.twig`, `admin/block/_form_accordion.html.twig`, `admin/block/_previa.html.twig` (células vazias da tabela não viram rótulo); parciais `themes/cetec/blocks/_accordion.html.twig` e `themes/atelie/blocks/_accordion.html.twig`; `_block.html.twig` de cetec, moderno, wab e atelie; `_header.html.twig` de cetec, moderno, wab e atelie.
- CSS: componente `.wab-sanfona` em `assets/styles/app.css`; classes `at-accordion` em `themes/atelie/_styles.html.twig`.
- Fixtures: a página `todos-os-blocos` de `cetec.localhost` e `atelie.localhost` ganha uma sanfona.
- Banco: nenhuma coluna nova (tudo em `page_block.config`). Duplicação, exportação e importação já levam o `config` inteiro.
- Produção: deploy com `git pull` e `./build.sh`, sem migration (escrita, autorizada pelo Jonas no pedido).
