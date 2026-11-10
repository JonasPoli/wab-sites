# Design

## Context

Leitura do código em 10/11/2026 (só leitura, nada em produção):

- **Formulário:** `templates/superadmin/tenant/edit.html.twig`.
  - As linhas 600 a 664 montam a seção "Configurações de Tipografia dos Títulos (H1 a H5)".
  - A lista de famílias é um mapa Twig fixo, `fontFamilies` (linhas 603 a 609): Inter, Roboto, Playfair Display, Montserrat e Outfit, cada uma com um rótulo.
  - Os pesos vão de 300 a 900 para todas as famílias (linhas 610 a 618).
  - Cada título tem três selects: `h{i}_font`, `h{i}_size` e `h{i}_weight` (linhas 625 a 664). Sem nada gravado, aparecem marcados Outfit, "Padrão do Tema" e 400 (linhas 628 a 630).
  - O cadastro de tenant novo (`new.html.twig`) não tem campos de fonte.
- **Gravação:** `SuperAdminController::populateTenantFromRequest()`, linhas 770 a 778.
  - Grava os cinco títulos sempre, com Outfit, tamanho vazio e peso 400 quando o campo não vem no POST.
  - Não confere se a família está na lista.
  - O mesmo método atende o `tenantNew()` (linha 39). Por isso, todo tenant criado pelo `/superadmin` nasce com Outfit 400 em H1 a H5.
- **Armazenamento:** `Tenant::$fontSettings` é uma coluna JSON anulável (`src/Entity/Tenant.php`, linhas 137 e 138; migration `Version20260520190129`), no formato `{"h1": {"font", "size", "weight"}, ...}`.
  - Vai e volta como está na exportação e na importação: `TenantExporter`, linha 155; `TenantImporter`, linha 216; `Tenant.php`, linhas 422 e 469.
  - A lista de fontes do formulário não mudou desde que foi criada (commit `e8d3a0d`, 20/05/2026). Logo, os valores gravados pelo formulário são sempre uma das cinco famílias.
- **Carregamento no site:** `App\Twig\TenantExtension::getCssVars()` (`src/Twig/TenantExtension.php`), chamada como `tenant_css_vars()` no `<head>` de `themes/cetec/base.html.twig` (linha 20), `themes/moderno/base.html.twig` (47), `themes/wab/base.html.twig` (70), `themes/noticias/base.html.twig` (27) e da página 404 (`bundles/TwigBundle/Exception/error404.html.twig`, 35).
  - Linhas 92 a 131: junta os pesos de cada família e monta um único `@import url('https://fonts.googleapis.com/css2?family=...:wght@...&display=optional')`. Título sem configuração pede Outfit 400 e 700.
  - O nome da família entra na URL só com os espaços trocados por `+` (linha 119), sem outro escape. O peso entra na regra CSS sem escape (linha 146).
  - Linhas 133 a 157: para cada título configurado, gera `hN { font-family: '<família>', <reserva> !important; font-weight: <peso> !important; font-size: ... }`. A reserva é `serif` só na Playfair Display (linha 141).
  - Não há arquivo de fonte local nem `@font-face` no projeto: tudo vem do Google Fonts.
- **Temas:**
  - O cetec declara `--font-headline: 'Outfit', 'Inter'` e `--font-body: 'Inter'` (`base.html.twig`, linhas 61 e 62), mas não carrega fonte própria.
  - O tema `noticias` carrega a Open Sans de 300 a 800 por `<link>` (`base.html.twig`, linha 31) e a usa no corpo e nos títulos (linhas 41 e 42).
  - As regras de `tenant_css_vars()` têm `!important` e passam por cima das do tema.
- **Google Fonts (consultado em 10/11/2026 com `curl` na API `css2`):**
  - Inter, Roboto, Montserrat e Outfit têm pesos de 100 a 900; a Playfair Display, de 400 a 900; a Open Sans, de 300 a 800.
  - Um peso fora da faixa é ignorado quando a família tem outro peso válido no pedido.
  - Quando todos os pesos pedidos de uma família estão fora da faixa, a família some da resposta. Se ela for a única família do pedido, a resposta é HTTP 400. Exemplos: `Open+Sans:wght@900` e `Playfair+Display:wght@300`.

## Goals / Non-Goals

**Goals:**
- Uma única lista das famílias dos títulos, lida pelo formulário, pela gravação e pelo `tenant_css_vars()`, para que acrescentar uma fonte seja mexer num lugar só.
- Nenhuma mudança no HTML dos sites que não usam a Open Sans nem um peso fora da faixa. O `<style>` gerado continua idêntico.
- Isolamento por tenant: a edição grava só o `fontSettings` do tenant aberto pelo id no `/superadmin`, que só o SuperAdmin acessa (Tenant não passa pelo filtro de tenant). O site lê o tenant do domínio pelo `TenantContext`. Nenhuma Page, PageSection ou PageBlock é lida nem gravada.

**Non-Goals:**
- Fonte do corpo do texto. O cetec declara Inter em `--font-body` mas não a carrega; isso fica como está.
- Fonte padrão por tema na edição: no tema `noticias`, por exemplo, deixar a Open Sans marcada quando não há nada gravado.
- Campos de fonte no cadastro de tenant novo e mudança no Outfit 400 que o `tenantNew()` grava hoje.
- Hospedar as fontes no servidor em vez de usar o Google Fonts.
- Mudar o `display=optional` do `@import`.

## Decisions

### 1. Catálogo das fontes dos títulos numa classe só
- Classe nova `App\Service\HeadingFonts` (final, sem estado), com uma constante. Para cada família, a constante guarda o rótulo do formulário, a reserva (`serif` ou `sans-serif`) e o peso mínimo e o máximo.
- Ordem e rótulos, mantendo os de hoje:

  | Família | Rótulo | Reserva | Pesos |
  |---|---|---|---|
  | Inter | Inter (Moderna, Geométrica) | sans-serif | 100 a 900 |
  | Roboto | Roboto (Neutra, Legível) | sans-serif | 100 a 900 |
  | Playfair Display | Playfair Display (Elegante, Serifa) | serif | 400 a 900 |
  | Montserrat | Montserrat (Expressiva, Geométrica) | sans-serif | 100 a 900 |
  | Open Sans | Open Sans (Humanista, Legível) | sans-serif | 300 a 800 |
  | Outfit | Outfit (Contemporânea, Original do Tema) | sans-serif | 100 a 900 |

  A Open Sans entra antes da Outfit para que a "Original do Tema" continue por último.
- Métodos estáticos: `choices()` (família → rótulo, para o select), `normalize(?string)` (família da lista ou `Outfit`), `fallback(string)` e `clampWeight(string $família, int|string $peso): int`.
- **Uso:**
  - `tenantEdit()` passa `HeadingFonts::choices()` ao template como `fontFamilies`, e o mapa fixo do Twig sai;
  - `populateTenantFromRequest()` grava `HeadingFonts::normalize()` da família recebida;
  - `getCssVars()` usa `normalize()`, `fallback()` e `clampWeight()`.
- **Alternativas descartadas:**
  - Só acrescentar a linha da Open Sans no Twig: a regra do peso precisaria de um segundo mapa no PHP, e as duas listas sairiam de sincronia na próxima fonte.
  - Constante na entidade `Tenant`: a faixa de pesos e a reserva são assunto de apresentação, não do modelo.
  - Trocar o formulário por Symfony Form com `ChoiceType`: o cadastro do tenant inteiro é feito à mão, e mudar isso é outro trabalho.

### 2. Peso fora da faixa trocado na hora de montar o CSS
- `getCssVars()` passa cada peso por `clampWeight()` antes de juntar os pesos do `@import` e antes de escrever a regra do título. Por exemplo: Open Sans 900 vira 800, e Playfair Display 300 vira 400.
- O valor gravado em `fontSettings` não muda, e o formulário continua oferecendo de 300 a 900 para todas as famílias.
- Abaixo do título da seção entra uma linha de ajuda: "A Open Sans tem pesos de 300 a 800 e a Playfair Display de 400 a 900. Fora disso, o site usa o peso mais próximo."
- **Alternativas descartadas:**
  - Filtrar os pesos no select com JavaScript, conforme a família escolhida: dá mais código no formulário e não resolve os valores já gravados nem os que chegam por importação.
  - Trocar o peso na gravação: muda o dado em silêncio e não corrige os tenants já gravados até alguém salvar de novo.
- O peso passa a ser sempre um inteiro na regra CSS. Isso fecha a entrada sem escape da linha 146.

### 3. Família fora da lista tratada como Outfit
- Na gravação (`normalize()` no controller) e na montagem do CSS (`normalize()` em `getCssVars()`). Assim nenhum texto livre de `fontSettings` entra no `@import` nem na regra do título, mesmo que venha de um ZIP importado.
- **Alternativa descartada:** aceitar qualquer família e só escapar o nome. Sem a faixa de pesos da família, o pedido ao Google Fonts pode voltar a falhar, como no caso da Open Sans 900.
- Nenhum valor gravado hoje cai nessa regra, porque a lista do formulário nunca mudou (ver Context). A tarefa 1.1 confere isso em produção, só leitura.

### 4. Sem migration
- `fontSettings` é JSON, sem enum nem tamanho limitado, e o mapeamento Doctrine não muda. O `doctrine:migrations:diff` deve responder que não há mudança, e a tarefa confere.
- A última migration em produção continua sendo a `Version20261110015129`.

### 5. Temas
- Nenhum template público é criado ou alterado. Muda só a saída de `tenant_css_vars()`, que aparece nos temas cetec, moderno, wab e noticias e na página 404. Todos os tenants reais usam o cetec.
- No tema `noticias`, a Open Sans escolhida nos títulos é pedida também pelo `@import`, além do `<link>` do tema. São dois pedidos ao `fonts.googleapis.com`, mas os arquivos do `fonts.gstatic.com` são os mesmos e vêm do cache do navegador.

## Risks / Trade-offs

- **[Site com Playfair Display e peso 300]** → Hoje o título desse site cai na fonte de reserva (`serif`). Depois do deploy, passa a sair na Playfair Display 400: é uma correção, mas muda a aparência. A tarefa 1.1 lista em produção (só leitura) os tenants nesse caso antes do deploy.
- **[Peso 900 mostrado no formulário e 800 no site]** → A linha de ajuda explica a troca. O valor gravado fica, e o site volta a usar o 900 se a família for trocada por uma que o tenha.
- **[Conflito com o `recaptcha-no-contato`]** → Os dois mexem no `edit.html.twig` e no `populateTenantFromRequest()`, em trechos diferentes. Quem implementar por último resolve o merge.
- **[Volta para a versão anterior]** → O código antigo carrega a Open Sans gravada, porque monta a URL com qualquer nome. Mas o select antigo não tem essa opção e mostra a primeira da lista (Inter). Salvar o tenant nessa situação grava Inter. Num rollback, não salvar os tenants com Open Sans até o código voltar.
- **[Google Fonts muda a faixa de uma família]** → A faixa do catálogo é fixa. Se o Google ampliar a faixa, nada quebra. Se reduzir, um peso pode sumir do `@import`. O risco é baixo, e o catálogo é o único lugar a ajustar.

## Migration Plan

1. Antes do deploy (produção, **leitura**): anotar o commit atual do servidor e listar o `font_settings` dos tenants (tarefa 1.1).
2. Deploy (produção, **escrita**, com aprovação do Jonas): `git pull` e `bin/console cache:clear`.
   - O `./build.sh` não é preciso, porque não muda CSS, JS, classe do Tailwind nem dependência. O Twig é recompilado pelo `cache:clear`.
   - Não há migration.
3. Depois (produção, **leitura**): HTTP 200 dos 8 domínios de cliente e o `<style>` de um site conferido.
4. Uso: o SuperAdmin escolhe a Open Sans nos títulos do tenant que precisar (por exemplo, o tenant 10) e salva. Isso é escrita no banco de produção pelo painel e é feita só com o OK do Jonas, com os valores anteriores anotados.
5. Rollback: `git checkout` do commit anotado e `cache:clear`. Os dados não precisam voltar (ver o risco da volta).

## Open Questions

- Rótulo da Open Sans no select: o design usa "Open Sans (Humanista, Legível)". Trocar o texto não muda o comportamento.
- Qual é o site do tenant 10 e de qual cliente ele é. O `context` lista os tenants só até o 9, de 09/11/2026. A resposta só define em qual ticket registrar o resultado e quais títulos recebem a Open Sans.
