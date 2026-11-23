# Design

## Context

A sanfona (change arquivada `2026-11-23-bloco-sanfona`) guarda cada tabela em `config.panels[].table = {header, rows}`, com `rows` de strings. O formulário mantém a tabela em memória e a grava como JSON num campo oculto por item; as células não têm `name`. Imagens de bloco hoje chegam pelo POST do "Salvar" (`imageFile`, `galleryFiles[]`, campos `banners[i][imageFile]`), o que exigiria um campo de arquivo por célula com nome renumerado a cada linha ou coluna mexida.

## Goals / Non-Goals

**Goals:**
- Logo na célula sem mudar o formato das células que só têm texto (as sanfonas gravadas continuam iguais).
- Um envio de imagem que outros blocos baseados em JSON possam reaproveitar (o "Mapa de representantes" vem logo depois).

**Non-Goals:**
- Trazer imagens ao colar uma tabela de página web (o servidor teria de baixar endereços de terceiros).
- Apagar arquivos que deixaram de ser usados (a galeria e o banner também não apagam).

## Decisions

### D1. Célula com imagem vira objeto; célula só de texto continua string

`rows[r][c]` é `string` (como hoje) ou `{text: string, image: string}` quando há imagem. Assim nenhum dado gravado muda, e o Twig distingue com `celula.image is defined`. `SanfonaDoBloco::tabela()` aceita os dois formatos, limpa o nome da imagem (só `[A-Za-z0-9._-]`, sem `/`) e considera vazia a célula sem texto **e** sem imagem. A linha com só uma imagem é mantida.

*Alternativa descartada:* matriz paralela `images[r][c]`. Teria de ser deslocada a cada linha ou coluna inserida ou removida, no JavaScript e no PHP.

### D2. Envio na hora: `POST /admin/block/midia` (`admin_block_media`)

- `ROLE_ADMIN` (o firewall do `/admin` já exige login e o domínio do site); token CSRF `block_media` no campo `_token`, emitido no formulário do bloco num `data-token-midia`.
- Aceita `image/jpeg`, `image/png`, `image/webp`, `image/gif` (MIME detectado pelo conteúdo, não pela extensão) até 5 MB e que o `getimagesize()` consiga ler; grava em `public/uploads/page_block_media/midia_<uniqid>.<ext>`.
- Resposta JSON: `{arquivo, url}` (a `url` é o arquivo original, para a miniatura do painel) ou `{erro}` com 400/403.
- A lógica fica em `App\Service\MidiaDoBloco` (`gravar(UploadedFile): string` e `arquivosDoConfig(array $config): string[]`), para o "Mapa de representantes" e o exportador usarem a mesma lista.

Isolamento: o arquivo não pertence a um tenant (como as outras pastas de `uploads/`); o nome é aleatório e só é citado no `config` de um bloco que passou pelo `blockOr404`. O envio em si não lê nem grava conteúdo de tenant.

*Alternativa descartada:* campo de arquivo por célula no POST do "Salvar". Os nomes teriam de ser renumerados a cada mudança e a prévia não teria a imagem antes de salvar.

### D3. Miniatura `logo_bloco`

Filtro novo no `liip_imagine.yaml`: WebP, qualidade 85, `thumbnail: {size: [320, 160], mode: inset}` (dobro do tamanho exibido). O `AquecerMiniaturasCommand` ganha `'uploads/page_block_media' => ['logo_bloco']`. Nos parciais, `<img src="{{ ('uploads/page_block_media/' ~ img)|thumb('logo_bloco') }}" width="160" height="80" loading="lazy" alt="…">` com `object-fit: contain` e `max-height: 80px`.

### D4. Editor da célula

Cada célula do editor ganha, ao lado do `textarea`, um botão pequeno "imagem" (`input type=file` escondido). Escolhido o arquivo, o JavaScript envia ao D2, guarda `{text, image}` no estado, mostra a miniatura com um "×" para remover e dispara a atualização do JSON e da prévia. Erro do envio aparece no aviso do item. Inserir/remover linha ou coluna já move a célula inteira (agora objeto), então a imagem vai junto. Colar substitui por strings (D1 garante o formato) e "Desfazer colagem" restaura o estado anterior, com imagens.

### D5. Exportação e importação

`TenantExporter::collectConfigImages()` e `TenantImporter::relocateConfigImages()` passam a chamar `MidiaDoBloco::arquivosDoConfig()` e a levar cada arquivo com a chave de mídia nova `page_block_media` (pasta `page_block_media` nos dois mapas). ZIP sem a pasta: o importador já ignora arquivo ausente.

### D6. Temas

Os dois parciais da sanfona (cetec, usado também por moderno, wab e noticias; e atelie) desenham a imagem antes do texto da célula. No modo cartão do celular, a célula com imagem não recebe `is-vazia`.

## Risks / Trade-offs

- [Arquivos órfãos quando o admin troca a imagem ou não salva] → aceitável (mesmo comportamento das outras imagens); pasta própria facilita uma limpeza futura.
- [Logo com fundo branco em modo escuro] → a miniatura fica sobre um fundo claro arredondado (`bg-white`, padding pequeno) nos dois modos.
- [GD do servidor não lê AVIF] → AVIF não é aceito no envio.

## Migration Plan

Sem migration. Deploy junto com `bloco-mapa-de-representantes`: backup, `git pull`, `./build.sh`, HTTP 200 nos domínios de cliente. Rollback: commit anterior + `./build.sh`. Atenção: o parcial antigo passa cada célula ao filtro `celula_da_sanfona`, que espera texto; uma célula objeto daria erro 500. Se já houver imagens gravadas em células, antes do rollback é preciso tirá-las pelo painel (ou manter esta versão).
