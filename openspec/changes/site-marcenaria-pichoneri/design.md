# Design

## Context

- O tenant é resolvido pelo host exato (`TenantSubscriber`); `www.<domínio>` e `<domínio>` são hosts diferentes. O site atual responde em `https://www.marcenariapichoneri.com.br` (o sem-www redireciona para o www).
- O precedente de endereço provisório é `ajope.wab.com.br` (tenant 10).
- O `/superadmin` exporta e importa um site inteiro em ZIP (`TenantExporter`/`TenantImporter`), com páginas, seções, blocos, mídia, notícias e categorias.
- Capa de notícia só aceita JPG ou PNG (o GD do servidor não lê AVIF); o texto da notícia passa pelo `ArticleBodySanitizer` (HTMLPurifier).
- Servidor de produção: 1 vCPU, 2 GB; sem cron nem worker. O site atual tem 31 fotos de portfólio, 24 logos, 3 fotos de depoimento, 2 MP4 de uso real e 40 capas de post.
- Não existe hoje nenhum mecanismo de redirecionamento no wab-sites.

## Goals / Non-Goals

**Goals:**
- Site novo no ar com o layout aprovado e todo o conteúdo útil do atual.
- Todo endereço antigo indexado responde 301 para o equivalente novo.
- Montagem reproduzível: o site é montado e conferido localmente e levado pronto para a produção por exportação/importação.

**Non-Goals:**
- Comentários, busca, RSS, página de autor e arquivos por data do blog antigo.
- Campos "Cidade" e "Tipo de projeto" no formulário de contato (o formulário do wab-sites tem nome, e-mail, telefone e mensagem; a mensagem orienta a informar cidade e tipo de projeto).
- Importar páginas do WordPress (a home é remontada à mão no layout novo; `/home2/` e `/pagina-exemplo/` são descartadas).
- Redirecionamento por padrão/regex ou para outro domínio.
- Desligar ou apagar o WordPress antigo (fica como está na hospedagem atual até o Jonas decidir).

## Decisions

### D1. Redirecionamento só no 404

Entidade `Redirect` (`TenantAwareInterface`): `tenant` (FK `ON DELETE CASCADE`), `fromPath` (255), `toPath` (500), `hits` (int), `createdAt`; índice único (`tenant_id`, `from_path`).

`RedirecionamentoSubscriber` em `KernelEvents::EXCEPTION`: age só quando a exceção é `NotFoundHttpException`, o pedido é principal, o método é GET ou HEAD e há tenant no `TenantContext`. Normaliza o caminho pedido (sem query, sem barra final, `/` para vazio, `rawurldecode`), procura no site do domínio e, achando, responde `RedirectResponse($toPath, 301)` e soma 1 em `hits` por `UPDATE` direto. Não achando, não faz nada (o 404 segue como hoje).

Por agir só no 404, **nenhuma rota existente muda de comportamento** e um redirecionamento nunca "esconde" uma página que existe. Custo: uma consulta indexada por 404.

Regras de cadastro (`/admin/redirecionamentos`, só admin do site, `workGroup` 0): `fromPath` começa com `/`, é gravado normalizado e é único no site; `toPath` começa com `/` e não com `//` (nada de destino externo), e é diferente do `fromPath`. Telas: lista paginada com contagem de acessos, formulário de inclusão, exclusão com CSRF. Item "Redirecionamentos" no menu do `/admin` para o admin.

Isolamento: a entidade tem `tenant_id` e passa pelo filtro; o subscriber consulta com o tenant do domínio; o painel grava com `TenantContext::requireTenant()`.

Exportação/importação: lista `redirects` no JSON (`from`, `to`); ZIP sem a chave importa sem redirecionamentos. Exclusão do site: a FK em cascata apaga; conferir a contagem na tela de exclusão do `/superadmin`.

*Alternativas descartadas:* regras no nginx/RunCloud (o usuário `runcloud` não edita o nginx, e as regras ficariam fora do site); rota "pega-tudo" (mudaria a ordem de casamento de rotas dos outros sites).

### D2. Importador de WordPress

`bin/console app:wordpress:importar <domínio-do-site> <url-do-wordpress> [--simular] [--limite=N]`

1. Resolve o tenant pelo domínio (como `app:conteudo-exemplo`) e liga o filtro de tenant.
2. Lê `<url>/wp-json/wp/v2/categories?per_page=100` e `<url>/wp-json/wp/v2/posts?per_page=100&page=N&_embed=1` (até acabar), com `HttpClientInterface`, `User-Agent` genérico e espera de 300 ms entre chamadas.
3. Categorias: cria `Category` raiz com o mesmo `slug` e nome quando não existe no site (`showInHeader` falso); ignora `sem-categoria`.
4. Para cada post, chave = `slug` do WordPress. Já existe notícia com esse slug → pula (o comando pode ser rodado de novo). Senão cria `Article`:
   - `title` (entidades HTML decodificadas), `slug`, `publishedAt` (campo `date`, horário de Brasília), `category` (a primeira), `status` publicado;
   - `body`: `content.rendered` limpo por `LimpadorDeConteudo` — remove shortcodes `[et_pb_…]`/`[/et_pb_…]`, remove `blockquote.wp-embedded-content` e `iframe.wp-embedded-content`, troca `href` que aponta para `/orcamento` do próprio site por `/#contato`, torna relativos os links para outros posts do mesmo site quando o slug foi importado (`/blog/<slug>`), e passa pelo `ArticleBodySanitizer`;
   - `summary`: `excerpt.rendered` sem HTML; se vier vazio ou com `[et_pb`, os primeiros 160 caracteres do corpo limpo (`TextoDeResumo`);
   - capa: `_embedded['wp:featuredmedia'][0].source_url` baixada para `public/uploads/article/`; JPG e PNG entram como estão; WebP é convertido para JPG com GD; outro formato ou falha de download → sem capa, com aviso; `coverCaption` = `alt_text` quando houver;
   - corpo vazio depois da limpeza → `status` rascunho.
5. Redirecionamentos (D1), criados se ainda não existem: `/<AAAA>/<MM>/<DD>/<slug>` → `/blog/<slug>` (para rascunho: → `/blog`); `/category/<slug>` → `/blog/categoria/<slug>`.
6. Relatório no fim: criados, pulados, rascunhos, sem capa, redirecionamentos, avisos. `--simular` mostra o relatório sem gravar nem baixar.

O comando não toca no tenant, nas páginas nem em notícias que não criou. É genérico (serve a qualquer WordPress com a API aberta), não leva nada da Pichoneri no código.

Serviços em `src/Service/Wordpress/`: `ClienteWordpress` (HTTP + paginação), `LimpadorDeConteudo` (puro, testável), `ImportadorDePosts` (orquestra). Testes de unidade no `LimpadorDeConteudo` com trechos reais (shortcode, incorporação de `/orcamento/`, link interno).

### D3. Montagem: local primeiro, produção por importação

1. Local: exportar o site de demonstração `atelie.localhost` (já tem a home do layout), descompactar, editar o JSON de dados (domínio `pichoneri.localhost`, nome, cores, textos, blocos) e trocar a mídia pelos arquivos reais baixados do site atual; recompactar e importar pelo `/superadmin`. Ajustes finos pelo `/admin`.
2. Local: `app:wordpress:importar pichoneri.localhost https://www.marcenariapichoneri.com.br`.
3. Conferência local completa (grupo 5 das tarefas).
4. Exportar `pichoneri.localhost` e importar em produção com o domínio provisório `pichoneri.wab.com.br`.
5. Aprovação; troca do domínio do tenant para `www.marcenariapichoneri.com.br`.

Assim a produção recebe um site pronto numa operação só, e o conteúdo fica fora do repositório de código.

### D4. Estrutura da home (layout aprovado + conteúdo que só o site atual tem)

Página `inicio`, home do tenant, Modo Landing Page ligado. Uma seção por linha:

| # | Seção (título no menu) | Bloco | Conteúdo |
|---|---|---|---|
| 1 | — (fora do menu) | `hero_split` | "Madeira, design e histórias feitas para durar." (destaque "histórias"), texto do layout, botão "Conheça nossos projetos" → seção 3, selo "88 / anos de tradição", foto `DSC02907` |
| 2 | — (fora do menu) | `highlights` | 88 anos de história · Projetos sob medida · Projeto em 3D · Execução completa |
| 3 | Projetos | `portfolio` | as 31 fotos únicas; título e categoria (Corporativo, Residencial, Detalhes) — classificação a confirmar com o cliente |
| 4 | Nossa história | `statement` | "Tradição artesanal. Precisão contemporânea.", texto institucional, "88 / anos de história", foto `DSC01010` |
| 5 | O que fazemos | `blurbs4` | Residencial, Corporativo, Industrial, Hospitalar (textos do layout) |
| 6 | Nosso processo | `steps` | 4 etapas do layout — a confirmar com o cliente |
| 7 | — | `video` | "Projeto 3D — Planeje sem erros, execute com perfeição." + MP4 `cama3.mp4` (ou o YouTube `7-y0py3nPrQ`, vertical) |
| 8 | — | `partner_logos` | Clientes: 11 logos |
| 9 | — | `testimonials` | 3 depoimentos com foto |
| 10 | — | `partner_logos` | Parceiros: 13 logos |
| 11 | — | `video` | "Do projeto à execução, excelência em cada etapa." + MP4 institucional `pichoneri.mp4` |
| 12 | Blog | `blog_posts` | 3 mais recentes |
| 13 | — | `cta` | "Seu próximo ambiente começa com uma boa ideia." → WhatsApp, nova aba |
| 14 | Contato | `contact` | "Peça seu orçamento" + mapa; a âncora `#contato` é a do bloco |

Menu: seções 3 a 6, 12 e 14 com `showInMenu`; botão de ação "Solicite um orçamento" → `#contato`. Barra superior ligada com horário, redes e telefone. Rodapé com newsletter ligada (a do wab-sites grava no banco; a atual é de demonstração).

Os MP4 do site atual têm de passar no limite de 30 MB do bloco `video`; o que passar disso entra como link do YouTube, ou é recomprimido antes.

### D5. Configuração do tenant

Nome "Marcenaria Pichoneri"; tema `atelie`; primária `#9b4509`, secundária `#c96a18`; no escuro (usadas dentro de seção escura) `#e7a15b` nas duas; fundos claros `#f7f5f0` e `#ebe6dc`; H1–H5 em Manrope; logo `logo-pichoneri-10.png`; favicon do site atual; e-mail de contato, telefone, endereço, WhatsApp (`https://wa.me/5516993502625?text=…`), Facebook, Instagram, horários, link do mapa; título de SEO "Marcenaria Pichoneri — Móveis e marcenaria sob medida em Araraquara" e descrição de até 160 caracteres; imagem de compartilhamento = foto do hero; blog ligado; newsletter ligada; frase do cabeçalho "Design & marcenaria sob medida". Chaves do reCAPTCHA v3 cadastradas para o domínio final.

### D6. Redirecionamentos do site

Além dos que o importador cria (44 posts e 5 categorias): `/home2` → `/`; `/pagina-exemplo` → `/`; `/author/wabmin` → `/blog`; `/category/sem-categoria` → `/blog`; `/project` → `/`; `/feed` → `/blog`; `/sitemap_index.xml` e `/wp-sitemap.xml` → `/sitemap.xml`; `/sitemap.html` → `/`. Conferência: script que percorre `referencia/post-sitemap.xml`, `category-sitemap.xml`, `page-sitemap.xml` e `author-sitemap.xml` e exige 200 ou 301→200 para cada endereço no site novo.

### D7. Domínio e virada

- Provisório: `pichoneri.wab.com.br` (registro DNS A para 162.243.56.194, domínio acrescentado ao webapp `app-wab-sites` no RunCloud, SSL Let's Encrypt). O tenant é importado com esse domínio. Enquanto provisório, o site sai fora dos buscadores: conferir o que o `RobotsController` responde e, se não bloquear, manter o tenant sem título de SEO público e pedir ao Jonas a decisão (não inventar mecanismo novo nesta mudança).
- Virada: acrescentar `www.marcenariapichoneri.com.br` e `marcenariapichoneri.com.br` ao webapp e emitir o SSL (o Let's Encrypt só emite depois do DNS apontado: usar a validação que o RunCloud oferecer ou aceitar alguns minutos sem HTTPS, combinados com o Jonas); trocar o domínio do tenant para `www.marcenariapichoneri.com.br` no `/superadmin`; apontar o DNS (registros A de `www` e da raiz) para 162.243.56.194; criar no RunCloud o redirecionamento da raiz para o `www` (a raiz não é tenant).
- Reversão: voltar os registros DNS para o servidor antigo (o WordPress continua lá, intacto) e o domínio do tenant para o provisório.
- Depois da virada: enviar o `/sitemap.xml` novo no Search Console (se houver acesso) e acompanhar os 404 por uma semana, criando redirecionamento para o que aparecer.

## Risks / Trade-offs

- [Queda de posição no Google na troca] → 301 de todo endereço indexado, mesmos slugs dos posts, títulos e descrições preenchidos, sitemap enviado.
- [Conteúdo do layout não confirmado pelo cliente ("88 anos", categorias, etapas)] → lista de pontos a confirmar entregue junto com o endereço provisório; o site só vira depois da aprovação.
- [Importador cria notícia com HTML quebrado do Divi] → limpeza testada com trechos reais + conferência visual de todos os 44 posts no ambiente local (são curtos).
- [31 fotos + capas geram muitas miniaturas num servidor de 1 vCPU] → depois da importação em produção, rodar `app:miniaturas:aquecer` uma vez, fora do horário comercial.
- [Formulário mais simples que o atual] → combinado como não-objetivo; avisar o cliente.
- [E-mail do contato cair em spam ou não chegar] → enviar um contato de teste no provisório e confirmar o recebimento em `contato@marcenariapichoneri.com.br` antes da virada.
- [DNS administrado por terceiro] → descobrir quem administra antes de marcar a data (pergunta em aberto).

## Migration Plan

1. Código (redirecionamentos + importador): migration aditiva (tabela `redirect`); deploy com backup, `git pull`, `./build.sh`, `migrate`, HTTP 200 nos domínios.
2. Conteúdo: montagem local → importação em produção no domínio provisório → aprovação → virada (D7).
3. Reversão do código: commit anterior + `./build.sh` (a tabela pode ficar). Reversão do site: D7.

## Open Questions

- Quem administra o DNS de `marcenariapichoneri.com.br` e qual é o cliente/ticket da Pichoneri no sistema WAB (descobrir na tarefa 6.1; não muda o que é construído).
- Há Search Console ou GA4 do site atual a manter? (Se houver, o id do GA4 entra no cadastro do site.)
