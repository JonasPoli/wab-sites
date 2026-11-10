# Design: video-do-youtube-na-noticia

## Context

Motivação em `proposal.md`; requisitos em `specs/noticias/spec.md`.

- **Formulário:** `admin/article/_form.html.twig` serve ao `/admin` (`staff: true`) e à área do jornalista (`staff: false`). Os dois gravam pelo `ArticleFormHandler::apply()`, que lê o request campo a campo e devolve a lista de erros.
- **Texto da notícia:** passa pelo `ArticleBodySanitizer`, que não aceita `<iframe>`.
- **Página:** `themes/noticias/article.html.twig` mostra o texto em `.nt-body` e, logo depois, a data final (`.nt-article__end`). A coluna tem 648 px (`tipografia-g1-no-tema-noticias`).
- **CSP:** o site não manda cabeçalho Content-Security-Policy (nem `nelmio`, nem `.htaccess`).

## Goals / Non-Goals

**Goals:** um vídeo do YouTube por notícia, por campo próprio, no fim do texto.

**Non-Goals:**
- Vimeo e outras plataformas.
- Vários vídeos.
- Vídeo no meio do texto.
- Tempo de início (`?t=`).
- Mostrar o vídeo na lista ou na capa.
- Gravar o arquivo do vídeo no servidor.

## Decisions

1. **Coluna `video_url` (255, anulável) em `article`.** Ela guarda o link já normalizado (`https://www.youtube.com/watch?v=<id>`). Criada por `doctrine:migrations:diff`.
   - **Alternativa descartada:** guardar só o id. O link completo é o que a pessoa reconhece ao editar e o que vai para a exportação.
2. **Normalização numa função estática `Article::youtubeId(?string): ?string`.**
   - Ela aceita os hosts `youtube.com`, `www.youtube.com`, `m.youtube.com`, `youtu.be` e `www.youtube-nocookie.com`.
   - Ela lê o id do parâmetro `v` (em `/watch`) ou do primeiro trecho do caminho (em `youtu.be/<id>`, `/shorts/<id>`, `/live/<id>` e `/embed/<id>`).
   - O id precisa casar com `^[A-Za-z0-9_-]{11}$`.
   - Link sem esquema ganha `https://` antes de ser lido.
   - O `ArticleFormHandler` grava `https://www.youtube.com/watch?v=<id>`; se a função devolver `null` com o campo preenchido, entra o erro do requisito.
3. **Player.**
   - No template, depois de `.nt-body`: `<figure class="nt-video">` com um `<iframe>`:
     - `src="https://www.youtube-nocookie.com/embed/<id>"`;
     - `loading="lazy"`;
     - `title="Vídeo: <título>"`;
     - `allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"`;
     - `referrerpolicy="strict-origin-when-cross-origin"` e `allowfullscreen`.
   - O id vem da função da decisão 2 e é escapado no Twig.
   - `.nt-video` usa `aspect-ratio: 16 / 9`, largura de 100% da coluna, cantos de 10 px como a capa e 32 px de margem embaixo.
   - O template só existe no tema `noticias`; os temas cetec, moderno e wab não têm página de notícia.
   - **Alternativa descartada:** um "facade" (miniatura que vira player no clique). Dá mais código, e o `loading="lazy"` já evita carregar o player antes da hora.
4. **Formulário.** O campo de URL fica logo depois do texto e antes dos links relacionados, com a dica "Cole o link do vídeo no YouTube, ex.: https://youtu.be/…".
5. **Exportação e importação.** Levam a chave `videoUrl`; um ZIP antigo importa sem vídeo.
6. **Isolamento.** O campo é gravado na notícia já resolvida pelo tenant do domínio (painel) ou do jornalista (área do jornalista). Nada muda no isolamento.

## Risks / Trade-offs

- **[Vídeo removido ou privado no YouTube]** O player mostra o aviso do próprio YouTube; o site não confere o vídeo.
- **[Privacidade]** O `youtube-nocookie.com` não grava cookie de rastreamento antes de o visitante dar play.

## Migration Plan

Local: migration, código e conferência em `noticias.localhost`. Produção (escrita, com aprovação do Jonas): backup (HEAD e dump), `git pull`, `doctrine:migrations:migrate` e `./build.sh` (mudou template); HTTP 200 nos 8 domínios de cliente e no ajope.
