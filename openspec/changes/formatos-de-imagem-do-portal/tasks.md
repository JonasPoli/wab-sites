# Tarefas: formatos-de-imagem-do-portal

## 1. Formatos

- [x] 1.1 Criar `ImageFormats` e o parâmetro `app.image_formats.disabled` (`IMAGE_FORMATS_DISABLED`). Conferir com `php -r` no Mac: (10/11/2026: o envio aceita `foto.jpg`, `FOTO.JPEG` e `a.PNG`, e recusa WebP, GIF, AVIF, AVIF renomeado para `.jpg` e arquivo sem extensão; `canThumbnail` dá true para JPEG, PNG, WebP e AVIF, e false para AVIF com `avif` desligado)
  - `acceptsUpload` aceita `foto.jpg`, `FOTO.JPEG` e `a.PNG` com conteúdo JPEG ou PNG;
  - recusa WebP, GIF, AVIF e um AVIF renomeado para `.jpg`;
  - `canThumbnail` é verdadeiro para JPEG, PNG e WebP e para AVIF sem nada desligado, e falso para AVIF com `avif` desligado.

## 2. Envio

- [x] 2.1 Validar a capa (`ArticleFormHandler`) e a foto do jornalista (cadastro e perfil), com o `accept` nos três formulários. Conferir no servidor local com `IMAGE_FORMATS_DISABLED=avif`: (10/11/2026, servidor local com `IMAGE_FORMATS_DISABLED=avif`, Playwright: capas AVIF e WebP pela área do jornalista voltam com "A imagem precisa ser JPG ou PNG." e nada é gravado; `FOTO.JPG` gravada; no cadastro, a foto AVIF volta com o erro e a PNG é gravada; o `accept` dos três campos é `.jpg,.jpeg,.png,image/jpeg,image/png`)
  - capa AVIF e capa WebP pela área do jornalista voltam com "A imagem precisa ser JPG ou PNG." e nada é gravado;
  - capa `FOTO.JPG` é gravada;
  - foto PNG no cadastro é gravada, e foto AVIF volta com o erro.

## 3. Exibição

- [x] 3.1 Criar o filtro `news_image` e usar nos quatro templates. Conferir no servidor local com `IMAGE_FORMATS_DISABLED=avif` e uma capa AVIF gravada por script numa notícia local: (10/11/2026: com capa AVIF gravada por SQL local numa notícia do "Leia também" com visualizações e foto AVIF na autora Ana Souza: home e `/noticias` com 2 referências ao `/uploads/article/teste-capa-avif.avif` (lista e Leia também) e nenhuma miniatura dele; "Mais lidas" com miniaturas na notícia também usa o original; `/autor/tomas-ribeiro-2` e `/autor/ana-souza` com o original; as outras imagens continuam em `/media/cache/resolve/`; o AVIF original responde 200 `image/avif`)
  - a home, a página do autor, "Mais lidas" e "Leia também" mostram `/uploads/article/<arquivo>.avif`, sem nenhum `/media/cache/resolve/` desse arquivo;
  - as outras notícias continuam com as miniaturas;
  - sem a variável, o AVIF volta a usar a miniatura.

## 4. Produção

- [ ] 4.1 Backup (escrita no servidor, com aprovação do Jonas): HEAD em `~/backups/`. Não há mudança no banco.
- [ ] 4.2 Deploy (escrita, com aprovação do Jonas): `git pull` e `./build.sh` com `nice`; HTTP 200 nos 8 domínios de cliente e no ajope.
- [ ] 4.3 Conferência (leitura): https://ajope.wab.com.br/autor/jonas-ernesto-poli mostra a capa `fhc-roda-viva-….avif` pelo endereço original, com 200; nenhuma página do ajope aponta para a miniatura desse arquivo.

## Workflow follow-up

- Arquivar depois do `portal-de-noticias`.
