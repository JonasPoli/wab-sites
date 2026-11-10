# Design: pre-titulo-longo-nos-blocos

## Context

- **Os 14 blocos do cetec com pré-título** (`blurbs4`, `contact`, `gallery`, `map`, `news_call`, `newsletter`, `page_list`, `partner_logos`, `stats`, `sub_categories`, `team`, `testimonials`, `text_image` e `text_only`) têm a mesma marcação: `<span class="inline-block bg-cetec-secondary/30 text-cetec-primary px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-3">{{ block.preTitle }}</span>`.
- **Os outros usos da pílula ficam fora:** os rótulos fixos ("Institucional", "Diferenciais", "Contato") da home e o nome da categoria na página do cetec.
- **Tailwind:** lê `templates/**/*.html.twig`, então as classes novas precisam estar escritas num template.

## Decisions

1. **Partial `themes/cetec/blocks/_pre_title.html.twig`** com a variável `text`.
   - Até 40 caracteres, sai a mesma `<span>` de hoje.
   - Acima disso, sai `<span class="inline-block max-w-3xl bg-cetec-secondary/20 text-cetec-primary px-5 py-3 rounded-xl text-sm md:text-base font-semibold leading-relaxed mb-4">`.
   - O destaque continua `inline-block`, para seguir o alinhamento do bloco (à esquerda ou centralizado).
   - No modo escuro (`dark:text-white`), o texto fica branco: com a primária do escuro (`#3b82f6`) sobre o fundo de 20% da secundária, o contraste era de 3,41:1; com branco, é de 12,56:1. A pílula curta fica como está.
   - O limite de 40 caracteres fica bem acima do maior rótulo dos sites de cliente (25).
2. **Os 14 blocos passam a incluir o partial** no lugar da `<span>`.
3. **Temas.** O cetec e o noticias (que usa os blocos do cetec) mudam, e o moderno e o wab não. O modo escuro do noticias usa as mesmas classes do cetec.
   - **Alternativa descartada:** um CSS só no tema `noticias` mirando as classes da pílula. Não daria para separar rótulo curto de frase longa, e a frase continuaria em caixa alta.

## Risks / Trade-offs

- **[Site de cliente com pré-título longo no futuro]** Ele passa a ver o destaque em vez da pílula. É a melhoria desejada.

## Migration Plan

Local: partial, `tailwind:build` e conferência na página "Todos os blocos" e numa página com pré-título longo. Produção junto com o `titulo-da-secao-opcional`, com `./build.sh`.
