# Tarefas: formatos-de-imagem-do-portal

## 1. Formatos

- [ ] 1.1 Criar `ImageFormats` e o parâmetro `app.image_formats.disabled` (`IMAGE_FORMATS_DISABLED`). Conferir com `php -r` no Mac:
  - sem nada desligado, aceita JPEG, PNG, WebP, GIF e AVIF, e o rótulo é "JPG, PNG, WebP, GIF ou AVIF";
  - com `avif` desligado, recusa `image/avif`, o rótulo é "JPG, PNG, WebP ou GIF" e o `accept` não tem AVIF.

## 2. Envio

- [ ] 2.1 Validar a capa (`ArticleFormHandler`) e a foto do jornalista (cadastro e perfil), com o `accept` nos três formulários. Conferir no servidor local com `IMAGE_FORMATS_DISABLED=avif`:
  - capa AVIF pela área do jornalista volta com "A imagem precisa ser JPG, PNG, WebP ou GIF." e nada é gravado;
  - capa WebP é gravada;
  - foto AVIF no cadastro volta com o erro.

## 3. Exibição

- [ ] 3.1 Criar o filtro `news_image` e usar nos quatro templates. Conferir no servidor local com `IMAGE_FORMATS_DISABLED=avif` e uma capa AVIF gravada por script numa notícia local:
  - a home, a página do autor, "Mais lidas" e "Leia também" mostram `/uploads/article/<arquivo>.avif`, sem nenhum `/media/cache/resolve/` desse arquivo;
  - as outras notícias continuam com as miniaturas;
  - sem a variável, o AVIF volta a usar a miniatura.

## 4. Produção

- [ ] 4.1 Backup (escrita no servidor, com aprovação do Jonas): HEAD em `~/backups/`. Não há mudança no banco.
- [ ] 4.2 Deploy (escrita, com aprovação do Jonas): `git pull` e `./build.sh` com `nice`; HTTP 200 nos 8 domínios de cliente e no ajope.
- [ ] 4.3 Conferência (leitura): https://ajope.wab.com.br/autor/jonas-ernesto-poli mostra a capa `fhc-roda-viva-….avif` pelo endereço original, com 200; nenhuma página do ajope aponta para a miniatura desse arquivo.

## Workflow follow-up

- Arquivar depois do `portal-de-noticias`.
