# Tasks

> Pré-requisitos: `blocos-novos-do-construtor`, `blog-nos-sites-institucionais` e `tema-atelie` implementadas, arquivadas e em produção. Leia `design.md` e `referencia/inventario.md`. Os grupos 1 e 2 são código; os grupos 3 a 8 são conteúdo e operação. **Toda escrita em produção exige aprovação explícita do Jonas na hora.**

## 1. Redirecionamentos

- [x] 1.1 Rodar `sh/regressao.sh capturar antes` com as fixtures carregadas (grupos padrão, `cetec`, `atelie`)
- [x] 1.2 Criar `src/Entity/Redirect.php` conforme D1 (implementando `TenantAwareInterface`, FK do tenant com `onDelete: 'CASCADE'`, índice único tenant + `fromPath`) e `src/Repository/RedirectRepository.php` (`findOneByPath(Tenant, string)`, `incrementHits(int $id)` por DQL `UPDATE`, listagem paginada); gerar a migration com `doctrine:migrations:diff`, conferir que só cria a tabela `redirect`, aplicar e validar com `doctrine:schema:validate`
- [x] 1.3 Criar `src/Service/CaminhoNormalizado.php` (função pura de D1: sem query, sem barra final, `/` para vazio, decodificado) com `tests/Service/CaminhoNormalizadoTest.php`; verificar com `vendor/bin/phpunit --filter CaminhoNormalizado`
- [x] 1.4 Criar `src/EventSubscriber/RedirecionamentoSubscriber.php` conforme D1; criar `tests/Controller/RedirecionamentoTest.php` (molde: `tests/Controller/SitemapERobotsTest.php`) com os cenários da spec: 301 com barra final e query, 404 sem cadastro, POST não redireciona, página existente tem prioridade, outro site responde 404; verificar com `vendor/bin/phpunit --filter Redirecionamento`
- [x] 1.5 Criar as rotas `/admin/redirecionamentos` (lista, incluir, excluir; `ROLE_ADMIN` + só `workGroup` 0, como as rotas de editores em `AdminContentController` linhas ~922–965) num controller novo `src/Controller/admin/AdminRedirectController.php`, os templates em `templates/admin/redirect/` no padrão de `templates/admin/newsletter/index.html.twig`, as validações de D1 e o item de menu em `templates/admin/wab_base.html.twig`; verificar os cenários "Destino externo", "Endereço antigo repetido" e "Contagem de uso" no painel local, e que um editor recebe 403
- [x] 1.6 `TenantExporter`/`TenantImporter`: lista `redirects`; conferir na tela de exclusão do `/superadmin` (e no método que conta o que será apagado) que os redirecionamentos entram na contagem; verificar exportando e importando um site com 3 redirecionamentos e excluindo o importado
- [x] 1.7 README: seção curta "Redirecionamentos" (para que serve, que só age no 404, como cadastrar)

## 2. Importador de WordPress

- [x] 2.1 Criar `src/Service/Wordpress/LimpadorDeConteudo.php` conforme D2.4 com `tests/Service/Wordpress/LimpadorDeConteudoTest.php` usando trechos reais (pegar 3 posts com `curl` de `https://www.marcenariapichoneri.com.br/wp-json/wp/v2/posts?per_page=3&_embed=1` e mais um com incorporação de `/orcamento/`, por exemplo um de jul/2023); verificar com `vendor/bin/phpunit --filter LimpadorDeConteudo`
- [x] 2.2 Criar `src/Service/Wordpress/ClienteWordpress.php` (categorias e posts paginados pelo cabeçalho `X-WP-TotalPages`, `User-Agent` genérico, espera de 300 ms, erro claro quando a API responde diferente de 200) e `ImportadorDePosts.php` conforme D2 (capa com conversão de WebP por GD; relatório)
- [x] 2.3 Criar `src/Command/ImportarWordpressCommand.php` (`app:wordpress:importar`, opções `--simular` e `--limite`), seguindo `SampleContentCommand` para resolver o tenant e ligar o filtro; verificar com `--simular` num site local: relatório com 44 posts, 5 categorias e 49 redirecionamentos, e nada gravado (`/admin/noticias` vazio)
- [x] 2.4 README: seção "Importar um blog do WordPress" com o comando, a simulação e o que conferir depois

## 3. Código em produção

- [x] 3.1 `sh/regressao.sh capturar depois` + `comparar` sem diferença; `vendor/bin/phpunit` inteiro; `openspec validate site-marcenaria-pichoneri --strict`
- [x] 3.2 Backup do banco de produção no servidor wabsites e anotação do HTTP dos domínios (leitura + arquivo de backup)
- [x] 3.3 Deploy (escrita): `git push origin main`; no servidor `git pull`, `./build.sh`, `bin/console doctrine:migrations:migrate --no-interaction`
- [x] 3.4 Conferência: HTTP 200 nos 8 domínios de cliente e em ajope.wab.com.br; um endereço inexistente de um deles continua 404

## 4. Montagem local do site

- [x] 4.1 Baixar do site atual, para `var/tmp/pichoneri/midia/` (pasta fora do git), com `curl` e `User-Agent` de navegador: a logo `uploads/2024/11/logo-pichoneri-10.png`, o favicon, as 31 fotos únicas da galeria (endereços em `referencia/home-atual.outline`), `DSC01010-Grande-e1736452134483.jpeg`, os 11 logos de clientes, os 13 de parceiros, as 3 fotos de depoimento e os MP4 `2024/11/cama3.mp4` e `2026/04/pichoneri.mp4`; conferir a contagem (31 + 24 + 3 + 2 + 3) e anotar o tamanho de cada MP4 (limite de 30 MB; acima disso, usar o vídeo do YouTube correspondente)
- [x] 4.2 Exportar `atelie.localhost` pelo `/superadmin`, descompactar em `var/tmp/pichoneri/zip/`, e editar o JSON de dados: domínio `pichoneri.localhost`, cadastro do site conforme D5, página `inicio` com as 14 seções de D4 (textos do layout de referência e de `referencia/inventario.md`; depoimentos completos copiados do HTML da home atual), trocando os arquivos de mídia pelos reais com os mesmos nomes referenciados no JSON; remover a página `todos-os-blocos` e os posts de demonstração; recompactar
- [x] 4.3 Importar o ZIP pelo `/superadmin` local como `pichoneri.localhost`; criar o admin do site; verificar `http://pichoneri.localhost:8000/` com 200 e as 14 seções
- [x] 4.4 No `/admin` local: título e categoria das 31 fotos do portfólio (primeira classificação por observação das fotos, marcada "a confirmar"), texto alternativo descritivo em cada foto e em cada logo (nome da empresa), ordem das fotos com a melhor em primeiro
- [x] 4.5 Rodar `bin/console app:wordpress:importar pichoneri.localhost https://www.marcenariapichoneri.com.br --simular`, ler o relatório, e depois sem `--simular`; verificar 43 publicados + 1 rascunho, 40 com capa, 5 categorias; marcar as categorias "Tendências", "Dica de arquitetura" e "Dica de decoração" como quiser o menu (fora do cabeçalho)
- [x] 4.6 Cadastrar em `/admin/redirecionamentos` os avulsos de D6

## 5. Conferência local

- [x] 5.1 Abrir os 44 posts em `/blog/<slug>` e conferir texto sem marcação estranha, capa e data; corrigir no `/admin` o que a limpeza não resolveu (anotar o post e o problema em "Pendências")
- [x] 5.2 Criar `sh/conferir-redirecionamentos.sh <base-url> <arquivo-sitemap>...` (extrai os `<loc>`, troca o host pela base, segue redirecionamentos com `curl -sIL` e exige 200 no fim, listando os que falham) e rodar contra `http://pichoneri.localhost:8000` com os quatro sitemaps de `referencia/`; resultado: zero falha
- [x] 5.3 Comparar a home com o layout de referência em 1440, 1050, 720 e 390 px; enviar um contato de teste (aparece em `/admin/contact`), inscrever um e-mail na newsletter, testar o filtro do portfólio, os dois vídeos, o botão de WhatsApp e o menu no celular
- [x] 5.4 Lighthouse (celular) na home e num post: acessibilidade, práticas e SEO 100, desempenho ≥ 90; conferir título, descrição, canônico, Open Graph e JSON-LD da home (`Organization` com endereço e telefone) e de um post (`BlogPosting`); `/sitemap.xml` com home, blog, 43 posts e 5 categorias
- [x] 5.5 Escrever em "Pendências" a lista do que o cliente precisa confirmar: "88 anos" (e o texto institucional), categorias e títulos do portfólio, as 4 etapas do processo, nomes de clientes e parceiros, formulário sem "Cidade" e "Tipo de projeto"

## 6. Site provisório em produção (escrita, com aprovação)

- [ ] 6.1 Levantar (leitura): cliente e ticket da Pichoneri no sistema WAB (`GET /clients` e `GET /tickets?q=pichoneri` pela API, conforme o CLAUDE.md global), quem administra o DNS do domínio e se há Search Console/GA4; registrar em "Pendências"
- [x] 6.2 Com aprovação: criar o registro DNS `pichoneri.wab.com.br` → 162.243.56.194, acrescentar o domínio ao webapp `app-wab-sites` no RunCloud e emitir o SSL; verificar `curl -I https://pichoneri.wab.com.br/robots.txt` respondendo pelo wab-sites ("Domínio não configurado" bloqueando o rastreamento)
- [x] 6.3 Backup do banco de produção; exportar `pichoneri.localhost` no local e importar em produção pelo `/superadmin` com o domínio `pichoneri.wab.com.br`; criar o admin do site; rodar no servidor `bin/console app:miniaturas:aquecer` (fora do horário comercial)
- [x] 6.4 Conferência: `https://pichoneri.wab.com.br/` e `/blog` com 200, imagens servidas de `/media/cache/` sem redirecionamento 302, `sh/conferir-redirecionamentos.sh https://pichoneri.wab.com.br …` sem falha, contato de teste chegando ao e-mail combinado, e HTTP 200 nos 8 domínios de cliente e em ajope.wab.com.br
- [x] 6.5 Conferir como o site provisório responde em `/robots.txt` e nas metas de robôs; se estiver indexável, parar e pedir a decisão do Jonas (D7) antes de divulgar o endereço
- [ ] 6.6 Entregar ao Jonas o endereço provisório e a lista de "Pendências" para ele levar ao cliente (resposta no ticket do cliente pelo fluxo do CLAUDE.md global, se ele pedir)

## 7. Virada do domínio (escrita, com aprovação e data combinada)

- [ ] 7.1 Aplicar no site provisório os ajustes pedidos pelo cliente e repetir 5.2 e 5.4 em produção
- [ ] 7.2 Cadastrar as chaves do reCAPTCHA v3 do domínio final no `/superadmin` (o Jonas cria as chaves) e o id do GA4, se houver
- [ ] 7.3 Backup do banco; anotar os registros DNS atuais do domínio (para a reversão); acrescentar `www.marcenariapichoneri.com.br` e `marcenariapichoneri.com.br` ao webapp no RunCloud, com o redirecionamento da raiz para o `www`
- [ ] 7.4 Trocar o domínio do tenant para `www.marcenariapichoneri.com.br` no `/superadmin`, apontar o DNS (A de `www` e da raiz) para 162.243.56.194 e emitir o SSL assim que o DNS propagar
- [ ] 7.5 Conferência: `https://www.marcenariapichoneri.com.br/` 200 com o site novo; `http://` e sem-www chegando por 301 ao `https://www.`; `sh/conferir-redirecionamentos.sh https://www.marcenariapichoneri.com.br …` sem falha; contato de teste recebido; `/sitemap.xml` com endereços no domínio final; HTTP 200 nos outros 9 domínios
- [ ] 7.6 Enviar o sitemap no Search Console (se houver acesso) e atualizar o `context` de `openspec/config.yaml` com o tenant novo (id, domínio, cliente WAB, tema `atelie`, blog ligado)

## 8. Acompanhamento

- [ ] 8.1 Uma semana depois da virada: conferir em `/admin/redirecionamentos` os acessos, procurar 404 recorrentes (Search Console ou log do nginx, se o Jonas tiver acesso) e cadastrar o que faltar; registrar o resultado no ticket do cliente

## 9. Ajustes pedidos depois da montagem (18/11/2026)

- [x] 9.1 Publicar "O que está por vir em 2024?" (no site antigo está publicado, só com a imagem) e apontar o redirecionamento do endereço antigo para `/blog/o-que-esta-por-vir-em-2024`; conferido 200 e 301
- [x] 9.2 Capas dos 4 posts de 2026, que não têm imagem no site antigo (nem destacada, nem no corpo, nem anexada): quadro do vídeo anexado ao "Antes e Depois" e fotos de projetos do site antigo nos outros três; conferido: os 44 posts da listagem com imagem
- [x] 9.3 Aviso de envio em janela (`templates/pub/_aviso.html.twig`, nos cinco temas): conferido no local (sucesso) e em produção (erro da newsletter, sem gravar nada), OK fecha a janela
- [x] 9.4 Capa na lista de notícias do painel e `capa_pagina`/`admin_thumb` no `app:miniaturas:aquecer`: conferido no local (44 com capa, 6 sem) e o cabeçalho em produção; 81 miniaturas geradas e nenhuma imagem da listagem do blog dependendo de geração na hora

## Pendências

- Importação de teste em `atelie.localhost` (18/11/2026): 44 posts, 1 rascunho ("O que está por vir em 2024?", sem texto), 4 sem capa (os de 2026), 4 categorias novas + "Tendências" já existente, 49 redirecionamentos; segunda execução com 44 pulados e 0 novos.
- Mudança de rota em relação ao plano (pedido do Jonas em 18/11/2026): o tenant 11 (`pichoneri.wab.com.br`) já existe em produção, então o site é cadastrado direto nele pelo `/superadmin` e pelo `/admin` (Chrome), e o blog é importado no servidor com `app:wordpress:importar`, em vez de montar localmente e importar um ZIP (a importação de ZIP cria um site novo e não atualiza um existente).

## Execução em produção (18/11/2026)

- Código: deploy único junto com as outras três mudanças (ver `blocos-novos-do-construtor/tasks.md`, seção Deploy).
- Cadastro do tenant 11 pelo `/superadmin` no Chrome (marapossi.com.br): tema Ateliê, cores, Manrope nos títulos, logo, logo escura, favicon, contatos, endereço, mapa, horário, redes, barra superior com horário/redes/telefone, Modo Landing Page, blog e newsletter ligados, SEO do site e imagem de compartilhamento.
- Blog: `app:wordpress:importar pichoneri.wab.com.br https://www.marcenariapichoneri.com.br --link=/orcamento=/#contato --texto-link="peça seu orçamento"` no servidor: 44 posts (1 rascunho), 40 capas, 5 categorias, 49 redirecionamentos.
- Home: o `/admin` do tenant só abre no próprio domínio, e o `pichoneri.wab.com.br` está com o certificado autoassinado do RunCloud (o Chrome bloqueia). A home foi montada no servidor por `var/tmp/pichoneri/montar.php` (entidades do sistema, mesmo formato do painel; backup `wab-sites-antes-deploy-20261118-2222.sql.gz` antes): 14 seções, 32 fotos de portfólio categorizadas, 11 clientes, 13 parceiros, 3 depoimentos com foto, vídeo institucional, botão e frase do cabeçalho e texto curto do site. 13 redirecionamentos avulsos (home2, página de exemplo, autor, feed, sitemaps antigos, /orcamento, /contato): 62 no total.
- Conferência: `CURL_OPTS=-k sh/conferir-redirecionamentos.sh https://pichoneri.wab.com.br referencia/*-sitemap.xml` com 53 OK e 0 falha; os 43 posts publicados abrem sem erro e sem marcação do Divi; Lighthouse celular na home de produção com 100 nas quatro categorias (LCP 1,4 s); miniaturas geradas (`app:miniaturas:aquecer`), sem `/media/cache/resolve` no HTML.
- Certificado: `pichoneri.wab.com.br` passou a ter Let's Encrypt (emitido em 18/11/2026 às 21h16 UTC, fora desta sessão); antes servia o certificado autoassinado do RunCloud. Conferido no Chrome: site abre sem alerta.
- Levantamento (leitura, 18/11/2026): cliente WAB 121 "Pichoneri", projeto 78 "Demandas Gerais - Pichoneri", contrato 44 "Plano de manutenção 12h/mês" (ativo). Tickets recentes: 13456 (caminho do orçamento), 13348, 13340, 13212, 13190; nenhum sobre este redesign.
- Não feito: envio de contato de teste (o e-mail do site é o do cliente, contato@marcenariapichoneri.com.br); decisão sobre indexação do provisório (sai `index, follow`); virada do domínio.

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois da virada conferida.
- Decidir com o Jonas quando desligar o WordPress antigo e quem guarda o backup dele.
