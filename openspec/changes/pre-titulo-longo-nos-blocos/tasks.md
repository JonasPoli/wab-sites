# Tarefas: pre-titulo-longo-nos-blocos

- [x] 1.1 Criar o `_pre_title.html.twig` e usar nos 14 blocos. Conferir com `grep` que a `<span>` da pílula só sobra na home, na página e na categoria do cetec. (10/11/2026: 14 blocos trocados; a pílula só sobra na home, na página e na categoria do cetec)
- [x] 1.2 Conferir, no local, depois do `tailwind:build`: (10/11/2026, depois do `tailwind:build` e do `asset-map:compile` locais: em `/p/sobre` do `noticias.localhost`, o pré-título longo da AJoPE sai sem caixa alta, com 14 px a 390 e 16 px a 1280, `padding: 12px 20px`, cantos de 12 px e fundo de 20% da secundária; no Escuro, texto branco (12,56:1); a pílula de "Rótulo curto" no cetec local saiu com o mesmo HTML de antes)
  - um bloco com o pré-título longo da AJoPE sai sem caixa alta, com 14 px (16 px a partir de 768 px), `padding` de 12 px e 20 px, em 390 e 1280 px, nos modos claro e escuro;
  - um pré-título de 20 caracteres continua na pílula, com o HTML igual ao de antes, num site cetec local.
- [ ] 2.1 Produção (escrita, com aprovação do Jonas), junto com o `titulo-da-secao-opcional`:
  - deploy com `./build.sh`;
  - HTTP 200 nos 8 domínios de cliente e no ajope;
  - conferir a página https://ajope.wab.com.br/p/quem-somos com o destaque novo e os pré-títulos dos sites de cliente sem mudança.
