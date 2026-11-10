# Proposal

## Why

Na edição do tenant no `/superadmin` (por exemplo, `https://pkwy.com.br/superadmin/tenant/10/edit`), o campo "Fonte (Família)" dos títulos H1 a H5 só oferece Inter, Roboto, Playfair Display, Montserrat e Outfit. O Jonas pediu em 10/11/2026 mais uma opção na lista, a **Open Sans**.

Hoje não há como escolher a Open Sans: a lista está fixa no template, e o site só carrega a fonte que foi gravada no tenant. O tema `noticias` usa a Open Sans no texto e nos títulos, mas salvar a edição do tenant grava uma fonte para cada título, sempre uma das cinco da lista, e essa fonte passa por cima da do tema.

## What Changes

- O campo "Fonte (Família)" de cada título (H1 a H5) na edição do tenant passa a oferecer a **Open Sans**, ao lado das cinco fontes de hoje.
- Quando um título usa a Open Sans, o site carrega a fonte pelo Google Fonts, no mesmo `@import` das outras, e aplica `font-family: 'Open Sans', sans-serif` ao título.
- **Peso fora da faixa da família:** a Open Sans só tem os pesos de 300 a 800 no Google Fonts. O formulário continua oferecendo de 300 a 900 para todas as fontes, e um peso que a família não tem passa a ser trocado, no site, pelo peso mais próximo que ela tem (900 vira 800 na Open Sans). Sem isso, a Open Sans com peso 900 não carrega: conferido em 10/11/2026, o Google Fonts responde HTTP 400 quando é a única família pedida e omite a família quando há outras.
- A mesma regra corrige um caso que já existe: a Playfair Display só tem de 400 a 900, e hoje o peso 300 deixa essa família fora do `@import`.
- As famílias oferecidas, a fonte de reserva (`serif` ou `sans-serif`) e a faixa de pesos de cada uma ficam num único catálogo no código. O formulário, a gravação e o `tenant_css_vars()` passam a ler esse catálogo, e uma família fora dele é tratada como Outfit, a padrão de hoje.
- Sem migration: `fontSettings` é uma coluna JSON sem lista fixa de valores.

## Capabilities

### New Capabilities
<!-- nenhuma -->

### Modified Capabilities
- `gestao-de-sites`: entra o requisito que diz quais famílias de fonte a edição do tenant oferece para os títulos (agora com a Open Sans) e o que é gravado quando chega uma família fora da lista.
- `navegacao-e-aparencia`: o requisito "Variáveis de cor e fontes" passa a dizer como a família escolhida é carregada e aplicada: a fonte de reserva, o peso trocado pelo mais próximo que a família tem e a família desconhecida tratada como Outfit.

## Impact

- **Código:**
  - `templates/superadmin/tenant/edit.html.twig`: a lista `fontFamilies` deixa de ser fixa no template, e entra uma linha de ajuda sobre os pesos;
  - `src/Controller/superadmin/SuperAdminController.php`: `tenantEdit()` passa o catálogo ao template, e `populateTenantFromRequest()` só grava famílias do catálogo;
  - `src/Twig/TenantExtension.php`: `getCssVars()` passa a usar o catálogo para a fonte de reserva e para a faixa de pesos;
  - uma classe nova e pequena com o catálogo das fontes dos títulos.
- **Banco:** nenhuma migration. Os valores já gravados (`Outfit`, `Inter` etc.) continuam válidos.
- **Sites públicos:** todos os temas chamam `tenant_css_vars()` (cetec, moderno, wab e noticias, além da página 404). Um site só muda de aparência se tiver gravado um peso que a família não tem: hoje, só a Playfair Display com 300 cai nesse caso.
- **Exportação e importação:** `fontSettings` continua indo e voltando como está. Uma família desconhecida que venha de um ZIP é tratada como Outfit na hora de montar o CSS.
- **Dependência:** o `recaptcha-no-contato` também mexe no `edit.html.twig` e no `populateTenantFromRequest()`. As alterações ficam em trechos diferentes, e quem for implementar por último resolve o conflito de merge, se houver.
- **Fora do escopo:**
  - fonte do texto (corpo), que continua vindo do tema;
  - campos de fonte no cadastro de tenant novo (`new.html.twig`);
  - fonte padrão por tema na edição;
  - outras famílias além da Open Sans.
