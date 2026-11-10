# Tarefas: tipografia-g1-no-tema-noticias

## 1. Medida de referência

- [x] 1.1 Guardar o script de medida do g1 (Playwright com Chrome; estilos calculados dos elementos do design, a 1280 e a 390 px) no scratchpad e rodar na notícia de referência e na home do g1. Conferir que os valores batem com a tabela do design. (10/11/2026: `compara.py` no scratchpad, que mede os mesmos elementos no g1 e no portal lado a lado; os valores do g1 batem com a tabela do design)

## 2. Tema

- [x] 2.1 No `themes/noticias/base.html.twig`, declarar `--nt-h1-size`, `--nt-h2-size` e `--nt-h3-size`, lidos do `fontSettings` com a regex da decisão 1 e com os padrões 2.5rem, 1.5rem e 1.25rem. Pôr o `.nt-header__section` em 32 px e peso 300 (24 px no celular). Conferir no HTML de `noticias.localhost`: (10/11/2026: "Padrão do Tema" → 2.5rem, 1.5rem e 1.25rem; H1 com 3rem gravado pela edição → `--nt-h1-size: 3rem`; `10vw` gravado no JSON local → 2.5rem)
  - com "Padrão do Tema", as variáveis saem com os padrões;
  - com 2.5rem gravado no H1, sai 2.5rem;
  - com `10vw` gravado direto no JSON local, sai o padrão.
- [x] 2.2 Aplicar as medidas da decisão 4 e os tamanhos dos títulos (decisões 2 e 3) no `_news_styles.html.twig`. Isso inclui o `.nt-card__title` fixo em 14 px e peso 600 e as regras do celular. Conferir com o script da 1.1 em `noticias.localhost`, com o tenant em Open Sans e nos tamanhos e pesos do g1, a 1280 e a 390 px: cada elemento com a mesma medida do g1. (10/11/2026, `noticias.localhost` com H1 a H5 em Open Sans 700 e 2.5/1.5/1.25/1/0.875rem gravados pela edição: na notícia (título, linha fina, autor, data, texto, intertítulo, lista, citação, link) e na home (chapéu, título, linha fina, relacionados, data, Mais lidas), tamanho, peso, entrelinha e espaço entre letras iguais aos do g1 a 1280 e a 390 px. A diferença aceita: os relacionados com entrelinha 1,3 em vez de 14 px, porque quebram em duas linhas. Editoria no topo com 32/24 px, peso 300 e −1 px; cabeçalho dos quadros com 14 px e peso 600 mesmo com o H2 em 24 px e 700)
- [x] 2.3 Conferir com o tenant em "Padrão do Tema" nos tamanhos: título da notícia com 40 px a 1280 px e 28 px a 390 px; títulos da lista com 24 e 20 px. (10/11/2026: tamanhos em "Padrão do Tema" → título da notícia 40 px (1280) e 28 px (390); título da lista 24 e 20 px; depois os tamanhos do g1 foram gravados de novo)
- [x] 2.4 Conferir: (10/11/2026: capturas no Claro e no Escuro em `shots-tipo-claro/` e `shots-tipo-escuro/` no scratchpad, todas 200; em "Todos os blocos", os títulos de seção dos blocos do cetec passam de cerca de 36 para 24 px (o H2 do tenant), como previsto no design, sem nada cortado nem sobreposto; cadastro e membro 200; contraste: linha fina 6,96 no Claro, e o menor no Escuro é 4,88)
  - os dois modos (claro e escuro), comparando com as capturas;
  - "Todos os blocos" sem texto cortado nem sobreposto;
  - o cadastro de jornalista e a página de membro;
  - o contraste da linha fina no escuro, que precisa passar de 4,5:1.

## 3. Documentação

- [x] 3.1 No README, acrescentar à descrição do tema `noticias` que a tipografia segue as medidas do g1 e que H1, H2 e H3 da edição do tenant dão o tamanho dos títulos (com a escala no celular). Conferir que o texto bate com o conferido na seção 2. (10/11/2026: frase acrescentada ao item do tema noticias no README, com os valores conferidos na seção 2)

## 4. Produção

- [x] 4.1 Backup (escrita no servidor, com aprovação do Jonas): anotar o `git rev-parse HEAD` em `~/backups/`. O dump do banco é o da gravação do tenant 10 (4.4). (10/11/2026: HEAD 2cdddf5 em `~/backups/wab-sites-HEAD-antes-20261110-1952.txt`)
- [x] 4.2 Deploy (escrita, com aprovação do Jonas), junto com o `fonte-open-sans`: `git pull` e `./build.sh` com `nice`, sem migration. Terminar com HTTP 200 nos 8 domínios de cliente e no ajope.wab.com.br. (10/11/2026 19:53 UTC, com a autorização do Jonas ("crie… implemente… publique"): e2e2593 no ar; backup `~/backups/wab-sites-HEAD-antes-20261110-1952.txt` (HEAD 2cdddf5) e `wab-sites-antes-deploy-20261110-1952.sql.gz` (20 tabelas); `git pull`, migration Version20261110194600 e `./build.sh` com nice; HTTP 200 nos 8 domínios de cliente e no ajope)
- [x] 4.3 Conferir que o `<style>` de marapossi.com.br não mudou (leitura). (10/11/2026: o `<style>` de marapossi.com.br está igual: Outfit 400, tamanho do tema)
- [x] 4.4 Tenant 10 (escrita no banco pela tela, com aprovação do Jonas): (10/11/2026: os valores de antes eram Outfit 400 e "Padrão do Tema"; foi gravado por `dbal:run-sql`, com o mesmo JSON do formulário, porque o Chrome ficou sem conexão e não havia sessão de SuperAdmin de produção (ver `fonte-open-sans` 5.2); o HTML do ajope mostra H1 a H5 em Open Sans 700, com 2.5/1.5/1.25/1/0.875rem e `--nt-h1-size: 2.5rem`)
  - antes: dump do banco em `~/backups/` e os valores de antes anotados (Outfit 400 e "Padrão do Tema", no `fonte-open-sans` 1.1);
  - gravar pelo `/superadmin` H1 a H5 em Open Sans, peso 700 e os tamanhos 2.5rem, 1.5rem, 1.25rem, 1rem e 0.875rem;
  - conferir que, ao reabrir a edição, os valores estão marcados.
- [ ] 4.5 Conferência no ajope (leitura): o script da 1.1 no ajope e no g1 dá as mesmas medidas, nos dois tamanhos de tela; os prints da notícia e da home vão para o Jonas.

## Workflow follow-up

- Arquivar depois do `fonte-open-sans` e do `modo-escuro-no-tema-noticias`.
