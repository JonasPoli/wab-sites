# desempenho-das-paginas Specification

## Purpose
Define como a página pública é entregue ao visitante: imagem sempre pela biblioteca de miniaturas, em formato moderno e no tamanho em que aparece na tela; CSS e JavaScript servidos pelo próprio domínio e só onde são usados; cache dos arquivos estáticos; e o patamar de nota do PageSpeed que os sites precisam manter.

## Requirements

### Requirement: Imagem sempre pela biblioteca de miniaturas
Toda imagem enviada pelo painel e exibida numa página pública SHALL ser servida por uma miniatura da biblioteca de imagens, em WebP, nunca pelo arquivo original. Isso vale para banner, capa de página, imagem de bloco, galeria, foto de equipe, avatar de depoimento, logo de parceiro, logo e favicon do site, fundo de seção e capa da notícia, em todos os temas.

#### Scenario: Home de um site de cliente
- **WHEN** a home de um tenant do tema `cetec` é carregada
- **THEN** nenhum endereço de imagem do HTML aponta direto para `/uploads/`, e todas apontam para a miniatura gerada

#### Scenario: Formato que o servidor não converte
- **WHEN** a imagem gravada está num formato que o servidor não consegue converter
- **THEN** a página mostra o arquivo original no lugar da miniatura e continua abrindo, sem erro

#### Scenario: Miniatura ainda não gerada
- **WHEN** a miniatura de uma imagem é pedida pela primeira vez depois de o cache ser apagado
- **THEN** ela é gerada e servida com status 200, sem redirecionamento intermediário

### Requirement: Tamanho da miniatura compatível com a exibição
A miniatura servida SHALL ter, no máximo, o dobro da maior largura em que a imagem aparece na tela, contando a tela do celular e a do computador. Imagem que aparece em mais de uma largura SHALL ser oferecida em mais de um tamanho, para o navegador escolher.

#### Scenario: Banner principal
- **WHEN** o banner da home é carregado num celular de 390 px de largura
- **THEN** o navegador baixa a versão de celular do banner, não a de computador

#### Scenario: Logo do cabeçalho
- **WHEN** o logo do site é exibido com 44 px de altura
- **THEN** o arquivo baixado tem no máximo 88 px de altura

### Requirement: Dimensões declaradas em toda imagem
Toda `<img>` de página pública SHALL trazer `width` e `height` no HTML, com a proporção real da imagem servida, para o navegador reservar o espaço antes de baixá-la.

#### Scenario: Página com várias imagens
- **WHEN** uma página com banner, cartões e logos é carregada
- **THEN** todas as `<img>` trazem `width` e `height` e o deslocamento de layout (CLS) fica em 0,1 ou menos

### Requirement: Prioridade de carregamento das imagens
A imagem que forma o maior elemento visível da página (LCP) SHALL ser carregada com prioridade alta e sem adiamento, e SHALL ter `preload` no `<head>`. Toda imagem que começa fora da área visível SHALL ser adiada.

#### Scenario: Banner da home
- **WHEN** a home é carregada
- **THEN** a imagem do banner tem `fetchpriority="high"`, `loading="eager"` e um `preload` correspondente no `<head>`, com o mesmo endereço que a `<img>` usa

#### Scenario: Imagem no rodapé da página
- **WHEN** a página tem imagens abaixo da dobra
- **THEN** elas saem com `loading="lazy"` e não são baixadas antes de o visitante rolar

### Requirement: CSS e JavaScript servidos pelo próprio domínio
As páginas públicas MUST NOT depender de CSS ou JavaScript de domínio de terceiro para renderizar. Folha de estilo, fonte e biblioteca de ícones SHALL ser servidas pelo domínio do site.

#### Scenario: Home de qualquer tenant
- **WHEN** a home é carregada
- **THEN** nenhuma requisição de CSS, fonte ou JavaScript sai para `cdn.jsdelivr.net`, `unpkg.com`, `cdnjs.cloudflare.com` ou `fonts.googleapis.com`

#### Scenario: Fonte do site
- **WHEN** a fonte do site ainda está carregando
- **THEN** o texto aparece numa fonte de reserva em vez de ficar invisível

### Requirement: Biblioteca carregada só onde é usada
Biblioteca de carrossel e biblioteca de animação SHALL ser carregadas só nas páginas que têm, respectivamente, um carrossel e um elemento animado. A biblioteca de ícones SHALL trazer apenas os ícones usados pelos temas e pelos blocos cadastrados.

#### Scenario: Página sem carrossel
- **WHEN** uma página institucional sem banner e sem galeria é carregada
- **THEN** o JavaScript e o CSS do carrossel não são baixados

#### Scenario: Página com galeria
- **WHEN** uma página com galeria em carrossel é carregada
- **THEN** o carrossel funciona com o arquivo servido pelo próprio domínio

### Requirement: Cache dos arquivos estáticos
Arquivo estático cujo nome tem impressão digital de conteúdo (os de `/assets` e as miniaturas) SHALL ser servido com validade de 1 ano e marcado como imutável. Arquivo enviado pelo painel, cujo nome não muda quando o conteúdo muda, SHALL ser servido com validade de 30 dias.

#### Scenario: Folha de estilo compilada
- **WHEN** o CSS compilado do tema é pedido
- **THEN** a resposta traz validade de 1 ano e `immutable`

#### Scenario: Troca de imagem pelo painel
- **WHEN** o administrador troca uma imagem mantendo a página
- **THEN** o visitante que já tinha visitado o site vê a imagem nova em no máximo 30 dias, sem limpar o navegador

### Requirement: Nota de desempenho do PageSpeed
As homes dos 9 tenants em produção SHALL alcançar 100 nas categorias Desempenho, Acessibilidade e Práticas recomendadas do PageSpeed Insights, no celular e no computador, medidas depois do aquecimento do cache de imagens.

#### Scenario: Medição de cada site
- **WHEN** a home de cada um dos 9 domínios é medida no PageSpeed Insights, nos dois formatos
- **THEN** as três categorias marcam 100

#### Scenario: Medição logo após o deploy
- **WHEN** a medição é feita sem aquecer o cache de miniaturas apagado pelo `build.sh`
- **THEN** o resultado não vale como conferência, e a medição é refeita depois do aquecimento

### Requirement: Peso da página
A home de cada tenant SHALL transferir no máximo 1,5 MB no primeiro acesso, somando HTML, CSS, JavaScript, fontes e imagens.

#### Scenario: Site com muitas imagens na home
- **WHEN** a home do site que hoje baixa 17,9 MB em imagens é carregada
- **THEN** o total transferido fica em 1,5 MB ou menos e nenhum arquivo isolado passa de 300 KB
