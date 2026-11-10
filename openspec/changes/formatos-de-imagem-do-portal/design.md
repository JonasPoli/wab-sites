# Design: formatos-de-imagem-do-portal

## Context

Motivação em `proposal.md`; requisitos em `specs/noticias/spec.md`.

- **LiipImagine:** `driver: gd`, `cache: format_extension`, Twig em modo `legacy`. As miniaturas saem por `|imagine_filter('<filtro>')`, que devolve `/media/cache/resolve/<filtro>/<caminho>`. Na primeira visita, esse endereço gera o WebP.
  - Com AVIF e sem suporte no GD, a geração lança exceção e responde 500.
- **Servidor** (conferido em 10/11/2026, só leitura):
  - GD "bundled 2.1.0", sem AVIF e com WebP, no PHP 8.3 e no 8.4;
  - sem Imagick e sem `convert`, `vips`, `avifdec`, `ffmpeg` ou `cwebp`;
  - uma capa `.avif` em `public/uploads/article`.
- **Mac local:** o GD tem AVIF.
- **Usos de miniatura no tema `noticias`:**
  - `_article_item` (`news_thumb` ou `hero_banner_mobile`);
  - `_most_read` (`news_thumb`);
  - `_featured` (`news_square`);
  - `author` (`news_square`, foto).
  - A capa na página da notícia já usa o original.
- **Validação hoje:** a capa só é conferida com `isValid()`; a foto do jornalista com `str_starts_with(mime, 'image/')`.

## Decisions

1. **`App\Service\ImageFormats`.**
   - O mapa tipo MIME → (bit do `imagetypes()`, rótulo) é este: `image/jpeg` → `IMG_JPG`, "JPG"; `image/png` → `IMG_PNG`, "PNG"; `image/webp` → `IMG_WEBP`, "WebP"; `image/gif` → `IMG_GIF`, "GIF"; `image/avif` → `IMG_AVIF`, "AVIF".
   - Um formato é aceito se o bit estiver em `imagetypes()` e não estiver desligado no parâmetro `app.image_formats.disabled`, que vem da variável de ambiente `IMAGE_FORMATS_DISABLED` (padrão vazio). Esse parâmetro serve para reproduzir no Mac o servidor sem AVIF.
   - Métodos:
     - `acceptsMime(string)`;
     - `acceptsFile(string $path)`, que lê o MIME com `finfo`;
     - `acceptAttribute()` (`image/jpeg,image/png,…`);
     - `label()` ("JPG, PNG, WebP ou GIF").
   - **Alternativa descartada:** lista fixa no código. Ela ficaria errada no Mac (com AVIF) ou no servidor, se ele ganhar AVIF.
2. **Validação no envio.** O tipo MIME vem de `$file->getMimeType()`, que lê o conteúdo e não a extensão.
   - A capa é conferida no `ArticleFormHandler::apply()`.
   - A foto do jornalista, no cadastro e no perfil.
   - O erro é "A imagem precisa ser <label>.".
   - Os `<input type="file">` desses três formulários passam a ter `accept="<acceptAttribute()>"`.
3. **Filtro Twig `news_image(caminho, filtro)`.**
   - Se o arquivo em `public/<caminho>` existe e `acceptsFile()` diz que o servidor o abre, devolve o mesmo que `imagine_filter(filtro)`; senão, devolve `asset(caminho)`.
   - O resultado é guardado num array por requisição, porque cada arquivo aparece mais de uma vez na página.
   - Os quatro usos do tema passam a usar o filtro.
   - O CSS já fixa a proporção e o `object-fit: cover` dessas imagens (`.nt-item__img img`, `.nt-rank img`, `.nt-featured img`, `.nt-author img`). Por isso o original aparece no mesmo tamanho e recorte.
4. **O endereço `/media/cache/resolve/…` de um AVIF continua respondendo 500** se alguém o abrir direto. Nenhuma página passa a apontar para ele, e o tratamento disso no LiipImagine fica fora deste change.
5. **Isolamento.** Nada muda: a validação acontece no envio do próprio site, e o filtro só lê o arquivo.

## Risks / Trade-offs

- **[Original maior que a miniatura]** No caso raro de um formato não suportado, a lista baixa o arquivo inteiro. Hoje isso é um arquivo de 26 KB.
- **[HEIC de iPhone]** O Safari envia HEIC às vezes. Ele passa a ser recusado com a mensagem, e a pessoa reenvia em JPG.

## Migration Plan

- **Local:**
  - com `IMAGE_FORMATS_DISABLED=avif` no servidor de teste, reproduzir o erro (capa AVIF gravada por script) e conferir a volta do original;
  - conferir a recusa no envio.
- **Produção** (escrita, com aprovação do Jonas):
  - backup do HEAD, `git pull` e `./build.sh` (mudaram templates), sem migration;
  - conferir que a página do autor mostra a capa AVIF original.
