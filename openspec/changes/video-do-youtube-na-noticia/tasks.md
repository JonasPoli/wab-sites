# Tarefas: video-do-youtube-na-noticia

## 1. Dados

- [ ] 1.1 Acrescentar `videoUrl` a `Article` e a função `youtubeId()` (decisão 2). Gerar a migration por `doctrine:migrations:diff`, com só o `ADD video_url` anulável em `article`, e aplicar no localhost. Conferir com `php -r`:
  - `youtu.be/dQw4w9WgXcQ?si=x`, `https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30`, `m.youtube.com/watch?v=…`, `youtube.com/shorts/…`, `youtube.com/live/…` e `youtube.com/embed/…` dão `dQw4w9WgXcQ`;
  - `vimeo.com/123`, `youtube.com/watch` (sem `v`) e `youtube.com/watch?v=curto` dão `null`.

## 2. Formulário e página

- [ ] 2.1 Campo no `_form.html.twig` e validação no `ArticleFormHandler`. Conferir em `noticias.localhost`:
  - pela área do jornalista (jornalista aprovada das fixtures), criar uma notícia com `https://youtu.be/dQw4w9WgXcQ?si=abc`: grava `https://www.youtube.com/watch?v=dQw4w9WgXcQ`;
  - com `https://vimeo.com/123456`, volta com o erro e não grava;
  - apagar o link e salvar tira o vídeo;
  - pelo `/admin`, o campo aparece e grava do mesmo jeito.
- [ ] 2.2 Player no fim da notícia (decisão 3). Conferir:
  - a notícia com vídeo tem o `<iframe>` de `youtube-nocookie.com/embed/dQw4w9WgXcQ` depois do último parágrafo e antes da data final, com `loading="lazy"` e o título;
  - a largura é a da coluna e a proporção é 16:9, a 1280 e a 390 px, nos modos claro e escuro;
  - a notícia sem vídeo não tem player.
- [ ] 2.3 Exportação e importação com `videoUrl`. Conferir no `metadata.json` exportado de `noticias.localhost`.

## 3. Documentação

- [ ] 3.1 README: o campo de vídeo no item do tema `noticias`. Conferir que o texto bate com a seção 2.

## 4. Produção

- [ ] 4.1 Backup (escrita no servidor, com aprovação do Jonas): HEAD e dump do banco em `~/backups/`.
- [ ] 4.2 Deploy (escrita, com aprovação do Jonas): `git pull`, `doctrine:migrations:migrate` e `./build.sh` com `nice`; HTTP 200 nos 8 domínios de cliente e no ajope.
- [ ] 4.3 Conferência (leitura): `/jornalista/noticias/new` do ajope redireciona para o login (sem sessão); o Jonas cria uma notícia com vídeo e confere o player.

## Workflow follow-up

- Arquivar depois do `portal-de-noticias`.
