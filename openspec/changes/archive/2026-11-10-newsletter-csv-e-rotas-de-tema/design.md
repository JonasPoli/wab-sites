# Design: newsletter-csv-e-rotas-de-tema

## Context

Motivação em `proposal.md`. Estado atual, lido no código e em produção em 09/11/2026 (só leitura):

- `AdminContentController::newsletterExport()` monta o CSV com `sprintf("%d,%s,%s\n", …, $s->getCreatedAt()->format('Y-m-d H:i'))`. `NewsletterSubscriber` só tem `getSubscribedAt()`, e o `sprintf` não escapa vírgula nem aspas. Nenhum template aponta para `admin_newsletter_export`.
- O botão "Exportar CSV" de `templates/admin/newsletter/index.html.twig` chama `exportCSV()`, que lê a tabela da tela (Nome, E-mail, Data em `d/m/Y`) e envolve cada célula em aspas sem escapar as aspas internas.
- `WabPublicController::videos()` e `studies()` renderizam `themes/<tema>/videos.html.twig` e `studies.html.twig`, que só existem no tema wab. `articles.html.twig` existe nos três temas. Nos 8 domínios, `/videos` e `/estudos` respondem 500 e `/noticias` responde 200.
- O tema wab (padrão de tenant novo e tema do tenant `127.0.0.1` das fixtures) chama `path('pub_videos')`, `path('pub_video_show')`, `path('pub_studies')` e `path('pub_study_show')` em `_footer.html.twig`, `home.html.twig`, `videos.html.twig`, `video.html.twig`, `studies.html.twig` e `study.html.twig`. As páginas do wab tratam a lista vazia ("Nenhum vídeo publicado ainda").

## Goals / Non-Goals

**Goals:**

- Exportação da newsletter funcionando, com um caminho só (servidor) e CSV válido.
- Nenhum 500 em `/videos` e `/estudos`, em qualquer tema.

**Non-Goals:**

- Separador `;` ou BOM para o Excel em português; o arquivo continua com vírgula, como a exportação de hoje pelo navegador.
- Coluna de nome no CSV: o nome do inscrito é sempre vazio (ver `contato-e-newsletter`).
- Criar listas de vídeos ou estudos nos temas cetec e moderno, ou tirar essas páginas do tema wab.

## Decisions

### 1. CSV gerado com `fputcsv` e proteção contra fórmula

`newsletterExport()` escreve num `php://temp` com `fputcsv()` (vírgula, aspas quando preciso) o cabeçalho `id,email,data` e, por inscrito, `id`, e-mail e `getSubscribedAt()->format('Y-m-d H:i')`. A célula que começa com `=`, `+`, `-` ou `@` ganha um `'` na frente: `FILTER_VALIDATE_EMAIL` aceita e-mail como `=cmd@exemplo.com`, e a planilha executaria a fórmula. Os cabeçalhos da resposta (`text/csv; charset=UTF-8`, `attachment; filename="newsletter.csv"`) não mudam.

Isolamento: `NewsletterSubscriber` implementa `TenantAwareInterface`, e o `findAll()` no `/admin` passa pelo `tenant_filter` do domínio, então só saem os inscritos do site aberto. Com `isolamento-entre-sites`, o `/admin` também passa a exigir que o usuário seja do site.

Alternativa considerada: só trocar `getCreatedAt()` por `getSubscribedAt()`. Resolve o 500, mas um e-mail com vírgula ou aspas quebraria a linha.

### 2. O botão usa a rota; sai o JavaScript

O botão vira `<a href="{{ path('admin_newsletter_export') }}">` e a função `exportCSV()` é removida. Assim há uma exportação só, com os dados do banco e não da tela.

Alternativa considerada: manter a exportação pelo navegador e só consertar a rota. Ficariam dois CSVs diferentes para a mesma coisa.

### 3. 404 quando o tema não tem o template; as rotas ficam

Helper privado no `WabPublicController`, por exemplo `renderThemeOr404(string $template, array $context)`: se `$this->container->get('twig')->getLoader()->exists($this->theme($template))` for falso, lança `createNotFoundException('Página não encontrada.')`; senão, renderiza. Usado em `articles()`, `videos()` e `studies()`. Em `/noticias` nada muda, porque os três temas têm `articles.html.twig`. As rotas de detalhe continuam respondendo 404.

Temas: nenhum template público criado ou alterado. `videos.html.twig` e `studies.html.twig` existem só no wab; `articles.html.twig` existe no cetec, no moderno e no wab.

Alternativa considerada: remover as rotas `pub_videos`, `pub_video_show`, `pub_studies` e `pub_study_show`. O `path()` do Twig lançaria `RouteNotFoundException`, e o tema wab (rodapé e home) quebraria inteiro. Para isso seria preciso editar seis templates do wab, por funcionalidades que só exibem listas vazias. A resposta 404 resolve o erro sem mexer em template.

## Risks / Trade-offs

- [Quem abria o CSV no Excel em português via tudo numa coluna] → Igual a hoje; fora do escopo (Non-Goals).
- [O CSV traz e-mails de pessoas] → Só para quem acessa o `/admin` do site; na conferência, não colar o conteúdo em ticket nem em chat.
- [`/videos` e `/estudos` indexados com erro] → Passam a 404, o sinal certo para robôs.

## Migration Plan

1. Implementar e conferir em `localhost` (tema moderno) e `127.0.0.1` (tema wab) com as fixtures, trocando o tema de um tenant local para cetec numa das conferências.
2. Produção, com aprovação do Jonas (escrita): anotar o commit atual; `git pull` e `./build.sh` (mudou template do painel); sem migration e sem escrita no banco.
3. Conferência (leitura): HTTP 200 em `/` nos 8 domínios, 404 em `/videos` e `/estudos`, 200 em `/noticias`; o Jonas baixa o CSV em `marapossi.com.br` e `pkwy.com.br` (3 inscritos cada).
4. Volta: `git checkout <commit anotado>` e `./build.sh`.
