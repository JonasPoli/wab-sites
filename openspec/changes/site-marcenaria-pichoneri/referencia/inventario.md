# Inventário do site atual — marcenariapichoneri.com.br (coleta de 18/11/2026, somente leitura)

WordPress + Divi. Na prática: uma home única com âncoras + blog de 44 posts. API REST aberta (`/wp-json/wp/v2/`).
Estrutura bruta da home em `home-atual.outline`; lista de URLs em `post-sitemap.xml`, `category-sitemap.xml`, `page-sitemap.xml`.

## URLs

| Tipo | Qtde | Formato |
|---|---|---|
| Páginas | 3 | `/` (home), `/home2/` (home antiga, duplicada), `/pagina-exemplo/` (lixo) |
| Posts | 44 | `/AAAA/MM/DD/slug/` (um com slug numérico: `/2023/08/31/209467/`) |
| Categorias | 5 em uso | `/category/{slug}/`: `dica-de-decoracao` (22), `informativo` (9), `dica-de-arquitetura` (8), `tendencias` (4), `cuidados` (1); `sem-categoria` (0) |
| Autor | 1 | `/author/wabmin/` |
| Outros | — | `/project/` (vazio), `/feed/`, arquivos por data (`/2023/`, `/2023/07/`), `/?s=` |

Hoje: http e sem-www redirecionam para `https://www.`. Não existem `/blog/`, `/contato/`, `/orcamento/` (404).

## Home atual, em ordem

1. Banner em tela cheia com vídeo de fundo: H1 "Transformando ambientes, criando histórias.", "88 anos de excelência", parágrafo institucional, botões "Solicitar orçamento" e "Falar no WhatsApp".
2. "O que fazemos": 6 cartões (Materiais Sustentáveis, Montagem e Instalação, Residencial, Comercial, Industrial, Hospitalar) + vídeo vertical do YouTube `7-y0py3nPrQ` ("Planeje sem erros, execute com perfeição." / "Visualize seu projeto em 3D antes da execução…").
3. Citação: "Com Pichoneri não existe isso de 'não fazemos'. Tudo é possível." + vídeo vertical `JCvNa5kAlEo` ("Satisfação garantida em cada projeto.").
4. Três cartões para categorias do blog (Tendências, Arquitetura, Decoração).
5. Clientes: 11 logos (Pelegrini, Embraer, HEAB, Lupo, Unimed, Santa Casa, Citrotec, Damha, Fiac, Pau Seco, Unesp) — nomes tirados dos arquivos, confirmar.
6. "Ambientes acolhedores": vídeo vertical `50Z3dBqkmTA`, "Design inteligente, qualidade incomparável.", parágrafo e botão para um post.
7. Blog: carrossel dos 6 posts mais recentes.
8. Galeria "Cada imagem, uma história de perfeição": 31 fotos únicas, sem título nem categoria (`uploads/2025/01/` e `uploads/2025/06/`).
9. Parceiros: 13 logos (Dettagli, Decoara, Fazzio, Duratex, Renata Barbugli, SR Ferragens, Arauco, Guararapes, Tatiana Castro, Caio, Masisa, Blum, Madeiranit).
10. Vídeo institucional MP4 (`uploads/2026/04/pichoneri.mp4`): "Do projeto à execução, excelência em cada etapa."
11. Depoimentos (3, com foto, 5 estrelas): Marcia Nasser (Arquiteta), Vicente Carlos Meca (Cliente), Patricia Potenza (Decoradora de interiores). Textos completos no HTML da home.
12. "Projeto 3D": MP4 `uploads/2024/11/cama3.mp4`.
13. Contato "Peça seu orçamento": 3 passos, cartões de WhatsApp / endereço / e-mail, formulário (Nome, WhatsApp, E-mail, Cidade, Tipo de projeto, mensagem), imagem do mapa com link.
14. Botão flutuante de WhatsApp.

## Cabeçalho, rodapé, contatos

- Barra superior: "Seg à Qui: 7h30 às 17h30 – Sex: 7h30 às 17h", Facebook, Instagram, WhatsApp, telefone.
- Menu: O Que Fazemos, Parceiros, Depoimentos, Contato (âncoras) + botão "Peça seu orçamento". O blog não está no menu.
- Rodapé: newsletter (Mailchimp de demonstração, provavelmente sem funcionar), contatos, redes, "Desenvolvido por wab.com.br".
- Telefone/WhatsApp (16) 99350-2625 (`5516993502625`); contato@marcenariapichoneri.com.br; R. José Mussi, 141, Araraquara – SP, CEP 14803-045; facebook.com/marcenariapichoneri; instagram.com/marcenariapichoneri; mapa `maps.app.goo.gl/n5ohYyLpHoX1Uffg7` (-21.7907, -48.2274).

## Blog

- 44 posts (2019: 3, 2022: 9, 2023: 25, 2024: 3, 2026: 4), uma categoria por post, sem tags, autor único "wabmin".
- 40 com imagem destacada (os 4 de 2026 não têm). Textos curtos (60 a 530 palavras), sem imagem no corpo.
- 38 feitos no construtor Divi: o `content.rendered` da API vem com HTML limpo na maioria, mas o `excerpt` de alguns traz shortcode cru (`[et_pb_row]`).
- 13 posts têm link ou incorporação (`blockquote` + `iframe`) para `/orcamento/`, que dá 404.
- 1 post vazio: `/2024/01/16/o-que-esta-por-vir-em-2024/` (só título e imagem).
- Comentários abertos, nenhum publicado. Só 2 posts têm meta description.

## SEO e terceiros

- Yoast + Google XML Sitemaps. Home com título "Home - Marcenaria Pichoneri", sem meta description nem `og:image`.
- Sem GA4, GTM, Pixel ou reCAPTCHA no HTML. Fontes do Google, 3 iframes do YouTube.

## Não confirmado

- Destino do e-mail do formulário atual; se a newsletter funciona; nomes exatos de clientes e parceiros; existência de Search Console/GA4 fora do HTML; onde o domínio e o DNS são administrados.
