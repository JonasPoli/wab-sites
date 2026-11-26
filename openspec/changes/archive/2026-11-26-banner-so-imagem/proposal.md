# Proposal

## Why

O bloco Banner sempre desenha cada slide como fundo de um texto: escurece a imagem com uma camada (`hero-overlay` no cetec e no atelie, cor primária e degradê no moderno e no wab), põe título, subtítulo e botões por cima, aplica zoom ao passar o mouse e recorta a imagem em 16:9 com altura fixa de 550/750 px. Clientes que já mandam a arte pronta, como a Transmai (tenant 13, `transmai.wab.com.br`, bloco 147), precisam publicar só a imagem, sem efeito nenhum e sem perder pedaços dela, e hoje o painel nem deixa salvar um slide sem título.

## What Changes

- Opção nova no bloco Banner, no `/admin`: **"Só a imagem"** (`config.imageOnly`), que vale para todos os slides do bloco. Desligada por padrão; os banners que já existem continuam iguais.
- Site público, com a opção ligada, nos cinco temas (cetec, moderno, wab, noticias e atelie):
  - cada slide mostra só a imagem, sem camada escura, sem degradê, sem tom da cor do site, sem desfoque e sem zoom ao passar o mouse;
  - nada de pré-título, título, subtítulo nem botões, inclusive os botões fixos "Ver Nossos Cursos" e "Fale Conosco" do cetec;
  - a imagem aparece inteira, na proporção dela, sem recorte; com "100% de altura" ligado, ocupa a altura da tela e é recortada para preencher;
  - com mais de um slide, o carrossel continua com troca automática, setas e bolinhas.
- Painel, com a opção ligada:
  - título, subtítulo, texto e link do botão de cada slide ficam desabilitados (apagados e sem edição), com um aviso; o título deixa de ser obrigatório; os valores já gravados ficam guardados e voltam se a opção for desligada;
  - cada slide ganha o campo opcional **"Descrição da imagem"**, que não aparece na tela e vira o texto alternativo da imagem (leitores de tela, Google e a versão Markdown para agentes);
  - a prévia do bloco mostra o banner como ele sairá no site.
- Descrição da página para Google e redes sociais (quando vem do texto do conteúdo): o banner "só a imagem" entra só com a "Descrição da imagem" do primeiro slide ativo, nunca com o título ou o subtítulo guardados e invisíveis (decisão do Jonas em 26/11/2026, durante a implementação).
- Correção de um defeito anterior, achado na implementação: a prévia do painel não mostrava a imagem escolhida e ainda não salva em campos com colchetes (slides do banner, galeria, depoimentos, logos e equipe), descumprindo o requisito "Arquivo escolhido na prévia" (decisão do Jonas em 26/11/2026: corrigir para todos os blocos).
- Miniaturas novas que não recortam a imagem (`banner_inteiro` e `banner_inteiro_mobile`), usadas pela imagem e pelo `preload` do `<head>` quando o banner "só a imagem" é o primeiro elemento da página.
- Fora do escopo: link na imagem, imagem diferente para o celular, a opção valer só para alguns slides, e os banners antigos da home sem página montada (`HeroBanner`).

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `construtor-de-paginas`: requisitos novos para o banner "só a imagem" (desenho sem efeitos e sem texto, imagem inteira, carrossel, descrição da imagem, edição no painel com campos desabilitados, prévia e os cinco temas).
- `seo-das-paginas`: requisito novo sobre o que o banner "só a imagem" leva para a descrição montada a partir do conteúdo.

## Impact

- Código: `src/Controller/admin/AdminContentController.php` (`populateBlock`, grava `alt` em cada slide), `src/Service/PreviaDoBloco.php` (`slides()`), `src/Twig/AtivosExtension.php` (`filtrosDoLcp()`), `src/Command/AquecerMiniaturasCommand.php` (filtros novos de `uploads/page_block`), `src/Service/Seo/TextoDeResumo.php` (`daPagina()`).
- Configuração: `config/packages/liip_imagine.yaml` (dois filtros novos, `inset`).
- Templates: `admin/block/_form_banner.html.twig`; `admin/block/_previa.html.twig` (nomes dos arquivos escolhidos em JSON e proporção da imagem trocada); parcial novo `themes/_comum/_banner_so_imagem.html.twig`, incluído no começo de `themes/cetec/blocks/_banner.html.twig` (também usado pelo noticias), `themes/moderno/blocks/_banner.html.twig`, `themes/wab/blocks/_banner.html.twig` e `themes/atelie/blocks/_banner.html.twig`.
- Banco: nenhuma coluna nova (tudo em `page_block.config`). Duplicação, exportação e importação já levam o `config` inteiro.
- Isolamento: nada novo; o bloco continua sendo lido e gravado pela seção da página ou categoria do tenant.
- Produção: deploy com `git pull` e `./build.sh` (template, CSS e filtro de miniatura), sem migration. Depois, ligar a opção no bloco 147 da Transmai é tarefa do Jonas ou do cliente, pelo painel.
- Registro: ticket interno da WAB (cliente 196, projeto 96 "WAB Sites").
