# Proposta: video-do-youtube-na-noticia

## Why

O Jonas pediu em 10/11/2026 que o cadastro de notícias do portal da AJoPE (https://ajope.wab.com.br/jornalista/noticias/new) tenha um campo próprio para o link de um vídeo do YouTube, e que o vídeo apareça no fim do texto completo da notícia.

Hoje a notícia só tem foto de capa, texto e links relacionados. O editor de texto passa pelo sanitizador, que não aceita `<iframe>`, então não há como pôr um vídeo.

## What Changes

- **Campo novo:** "Vídeo do YouTube (link)", no formulário de notícia da área do jornalista e do `/admin`, que usam o mesmo formulário. É opcional, e só um vídeo por notícia.
- **Links aceitos:** os formatos comuns do YouTube:
  - `youtube.com/watch?v=…`, `youtu.be/…`, `youtube.com/shorts/…`, `youtube.com/live/…` e `youtube.com/embed/…`, com ou sem `www.` e `m.`;
  - o link é gravado no formato `https://www.youtube.com/watch?v=<id>`.
  - Qualquer outro link volta com o erro "Informe um link de vídeo do YouTube (youtube.com ou youtu.be).".
- **Página da notícia** (`/noticias/{slug}`): o vídeo aparece no fim do texto, antes da data final, num player do YouTube com largura da coluna e proporção 16:9. O player usa o domínio `youtube-nocookie.com` (modo de privacidade ampliada) e só carrega quando chega perto da tela.
- **Exportação e importação** do site levam o link.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `noticias`: entram requisitos sobre o campo de vídeo no cadastro de notícias e sobre o vídeo no fim da página da notícia. São requisitos novos, sem alterar os do `portal-de-noticias`.

## Impact

- **Banco:** uma migration com a coluna anulável `video_url` (255) em `article`.
- **Código:**
  - `Article` (campo e o id do vídeo);
  - `ArticleFormHandler` (validação e normalização);
  - `TenantExporter` e `TenantImporter`.
- **Templates:**
  - `admin/article/_form.html.twig`, usado pelo `/admin` e pela área do jornalista;
  - `themes/noticias/article.html.twig` e `_news_styles.html.twig`, que só existem no tema `noticias`.
- **Rede:** a página da notícia com vídeo passa a carregar o player do YouTube (`www.youtube-nocookie.com`). O site não tem Content-Security-Policy que precise mudar.
