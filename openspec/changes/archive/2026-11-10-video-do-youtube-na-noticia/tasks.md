# Tarefas: video-do-youtube-na-noticia

## 1. Dados

- [x] 1.1 Acrescentar `videoUrl` a `Article` e a função `youtubeId()` (decisão 2). Gerar a migration por `doctrine:migrations:diff`, com só o `ADD video_url` anulável em `article`, e aplicar no localhost. Conferir com `php -r`: (10/11/2026: `Version20261110204105` só com `ADD video_url`; `youtubeId` dá `dQw4w9WgXcQ` para `youtu.be/…?si=x`, `watch?v=…&t=30`, `m.youtube.com`, `/shorts/`, `/live/…?si=1` e `/embed/`; dá null para Vimeo, `/watch` sem `v`, id curto, `evil.com/watch?v=…` e `youtube.com.evil.com`)
  - `youtu.be/dQw4w9WgXcQ?si=x`, `https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30`, `m.youtube.com/watch?v=…`, `youtube.com/shorts/…`, `youtube.com/live/…` e `youtube.com/embed/…` dão `dQw4w9WgXcQ`;
  - `vimeo.com/123`, `youtube.com/watch` (sem `v`) e `youtube.com/watch?v=curto` dão `null`.

## 2. Formulário e página

- [x] 2.1 Campo no `_form.html.twig` e validação no `ArticleFormHandler`. Conferir em `noticias.localhost`: (10/11/2026, Playwright: a jornalista aprovada criou a notícia 291 com `https://youtu.be/dQw4w9WgXcQ?si=abc`, gravada como `https://www.youtube.com/watch?v=dQw4w9WgXcQ`; Vimeo voltou com o erro; apagar o link e salvar deixou `video_url` vazio; pelo `/admin`, o campo aparece e gravou `watch?v=…&t=30` normalizado)
  - pela área do jornalista (jornalista aprovada das fixtures), criar uma notícia com `https://youtu.be/dQw4w9WgXcQ?si=abc`: grava `https://www.youtube.com/watch?v=dQw4w9WgXcQ`;
  - com `https://vimeo.com/123456`, volta com o erro e não grava;
  - apagar o link e salvar tira o vídeo;
  - pelo `/admin`, o campo aparece e grava do mesmo jeito.
- [x] 2.2 Player no fim da notícia (decisão 3). Conferir: (10/11/2026: o `<iframe>` de `https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ` tem `loading=lazy` e o título "Vídeo: <título>"; numa notícia com texto, a ordem no HTML é texto → player → data final; 648×365 px a 1280 e 358×201 a 390 (16:9), nos modos claro e escuro, com o vídeo carregado; 28 px acima quando não há texto; a notícia sem vídeo não tem player)
  - a notícia com vídeo tem o `<iframe>` de `youtube-nocookie.com/embed/dQw4w9WgXcQ` depois do último parágrafo e antes da data final, com `loading="lazy"` e o título;
  - a largura é a da coluna e a proporção é 16:9, a 1280 e a 390 px, nos modos claro e escuro;
  - a notícia sem vídeo não tem player.
- [x] 2.3 Exportação e importação com `videoUrl`. Conferir no `metadata.json` exportado de `noticias.localhost`. (10/11/2026: o `metadata.json` exportado de `noticias.localhost` tem `videoUrl` em todas as notícias, preenchido nas 2 com vídeo)

## 3. Documentação

- [x] 3.1 README: o campo de vídeo no item do tema `noticias`. Conferir que o texto bate com a seção 2. (10/11/2026: frase do vídeo e dos formatos de imagem no item do tema `noticias` do README)

## 4. Produção

- [x] 4.1 Backup (escrita no servidor, com aprovação do Jonas): HEAD e dump do banco em `~/backups/`. (10/11/2026 20:44 UTC, com a autorização do Jonas ("ok, pode fazer"): HEAD dcebe3d em `~/backups/wab-sites-HEAD-antes-20261110-2044.txt` e dump `wab-sites-antes-deploy-20261110-2044.sql.gz` (20 tabelas))
- [x] 4.2 Deploy (escrita, com aprovação do Jonas): `git pull`, `doctrine:migrations:migrate` e `./build.sh` com `nice`; HTTP 200 nos 8 domínios de cliente e no ajope. (10/11/2026 20:45 UTC: cc18e2b no ar; `git pull`, migration Version20261110204105 e `./build.sh` com nice; HTTP 200 nos 8 domínios de cliente e no ajope)
- [ ] 4.3 Conferência (leitura): `/jornalista/noticias/new` do ajope redireciona para o login (sem sessão); o Jonas cria uma notícia com vídeo e confere o player. (10/11/2026: `/jornalista/noticias/new` do ajope sem sessão → 302 para `/login`. Pendente: o Jonas criar uma notícia com vídeo e conferir o player)

## Workflow follow-up

- Arquivar depois do `portal-de-noticias`.
