## ADDED Requirements

### Requirement: Campo de busca do portal
No tema `noticias`, o cabeçalho SHALL ter, no canto direito e em todas as larguras de tela, um botão de lupa com nome acessível "Buscar" que mostra e esconde um campo de busca logo abaixo da barra, já com o foco no campo; o menu lateral SHALL ter o mesmo campo. Enviar o campo SHALL abrir `/busca?q=<termo>`.

#### Scenario: Busca pelo cabeçalho no celular
- **WHEN** um visitante a 390 px toca na lupa, digita "festival" e confirma
- **THEN** o navegador abre `/busca?q=festival`

### Requirement: Resultados da busca
`/busca?q=<termo>` SHALL listar as notícias visíveis do site do domínio que têm todas as palavras do termo no título, no chapéu, na linha fina ou no texto, sem diferenciar maiúsculas nem acentos, primeiro as que têm alguma palavra no título e depois da mais recente para a mais antiga, 20 por página com `?pagina=N`, com o título "N resultados para "<termo>"". Páginas institucionais do site cujo título tem todas as palavras SHALL aparecer num quadro "Páginas".

#### Scenario: Palavra sem acento
- **WHEN** o site tem a notícia "Mutirão recupera praças e canteiros" e alguém busca "mutirao pracas"
- **THEN** a notícia aparece nos resultados

#### Scenario: Notícia de outro site
- **WHEN** outro site tem uma notícia com "festival" e alguém busca "festival" no portal da AJoPE
- **THEN** só as notícias da AJoPE aparecem

#### Scenario: Sem resultado
- **WHEN** ninguém usou o termo "xyzw"
- **THEN** a página mostra "Nenhuma notícia encontrada para "xyzw"." e o campo de busca

### Requirement: Regras do termo de busca
O termo SHALL ser cortado em 100 caracteres e em até 5 palavras, ignorando palavras com menos de 2 letras; termo vazio ou só com palavras ignoradas SHALL mostrar só o campo de busca, sem resultados. Rascunhos, notícias agendadas e notícias de outro site MUST NOT aparecer.

#### Scenario: Termo vazio
- **WHEN** alguém abre `/busca` sem `q`
- **THEN** a página mostra só o campo de busca

#### Scenario: Notícia agendada
- **WHEN** uma notícia agendada para amanhã tem a palavra buscada
- **THEN** ela não aparece nos resultados

### Requirement: Busca fora dos buscadores e dos outros temas
A página `/busca` SHALL sair com `noindex` e ficar fora do `/sitemap.xml`; em site de outro tema, `/busca` SHALL responder 404.

#### Scenario: Site cetec
- **WHEN** alguém abre `/busca?q=teste` num site com tema cetec
- **THEN** a resposta é 404
