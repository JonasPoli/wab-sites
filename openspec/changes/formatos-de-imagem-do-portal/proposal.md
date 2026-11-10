# Proposta: formatos-de-imagem-do-portal

## Why

Em 10/11/2026, a miniatura de uma notícia na página do autor (https://ajope.wab.com.br/autor/jonas-ernesto-poli) deu erro 500 e apareceu quebrada:

`/media/cache/resolve/news_thumb/uploads/article/fhc-roda-viva-6af37f209913d032366716.avif`

A capa foi enviada em **AVIF**. O PHP do servidor (8.3 e 8.4) tem o GD sem suporte a AVIF e não tem Imagick nem outro conversor, então o LiipImagine não abre o arquivo para gerar a miniatura (filtros `news_thumb`, `news_square` e `hero_banner_mobile`). O arquivo original abre normalmente (`image/avif`, 200), e os navegadores atuais mostram AVIF.

Hoje o envio da capa (`/admin` e área do jornalista) e da foto do jornalista (cadastro e perfil) aceita qualquer `image/*`. Em produção há uma capa nessa situação.

## What Changes

- **No envio, só os formatos que o servidor converte.** A capa da notícia e a foto do jornalista passam a aceitar só os formatos que o PHP do servidor consegue abrir: hoje JPG, PNG, WebP e GIF.
  - Um formato fora da lista (AVIF, HEIC, BMP, SVG…) volta com "A imagem precisa ser JPG, PNG, WebP ou GIF." e não é gravado.
  - O seletor de arquivo passa a sugerir esses formatos.
  - Se um dia o servidor ganhar suporte a AVIF, ele entra na lista sem mudar o código.
- **Imagem já gravada que o servidor não converte:** em vez da miniatura, o tema mostra o arquivo original, com o mesmo recorte por CSS. Isso vale para a lista de notícias (home, editoria, `/noticias`, página do autor), "Mais lidas", "Leia também" e a foto do autor. A capa AVIF que já está no ar volta a aparecer sem nenhuma troca de arquivo.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `noticias`: entram requisitos sobre os formatos de imagem aceitos no envio e sobre a imagem que não vira miniatura.

## Impact

- **Código:**
  - serviço novo `App\Service\ImageFormats`, que lê os formatos com `imagetypes()` do GD;
  - validação no `ArticleFormHandler` (capa) e no `JournalistSignupController` e no `JournalistAreaController` (foto);
  - filtro Twig novo (`news_image`), que devolve a URL da miniatura ou a do original.
- **Templates do tema `noticias`:** `_article_item`, `_most_read`, `_featured` e `author`. Também os `accept` do `admin/article/_form.html.twig`, do cadastro e do perfil.
- **Sem migration.**
- **Fora do escopo:**
  - converter AVIF no servidor;
  - os outros uploads do painel (banners, blocos, logos), que continuam como estão.
