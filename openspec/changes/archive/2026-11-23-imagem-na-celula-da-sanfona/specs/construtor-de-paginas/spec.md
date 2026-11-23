# Spec Delta

## ADDED Requirements

### Requirement: Imagem na célula da sanfona
Cada célula da tabela de um item da sanfona SHALL poder ter uma imagem, sozinha ou acima do texto da célula. No site, a imagem SHALL sair como miniatura de até 160 px de largura e 80 px de altura, sem distorcer, com o texto alternativo igual ao texto da célula ou, sem texto, ao cabeçalho da coluna.

#### Scenario: Logo na coluna Logo
- **WHEN** a célula da coluna "Logo" da linha de "J. G. PACHECO & CIA LTDA" tem uma imagem e nenhum texto
- **THEN** a célula mostra a miniatura do logo com o texto alternativo "Logo"

#### Scenario: Célula só com imagem no celular
- **WHEN** a tabela tem cabeçalho e é vista numa tela de 390 px
- **THEN** o cartão da linha mostra "Logo" seguido da imagem, e a célula não é escondida como vazia

#### Scenario: Linha só com imagem
- **WHEN** o admin grava uma linha em que todas as células estão vazias, menos uma com imagem
- **THEN** a linha é gravada

### Requirement: Escolher a imagem da célula no painel
No editor da tabela, cada célula SHALL ter um botão para escolher uma imagem do computador, mostrar a miniatura depois de enviada e permitir trocar ou remover a imagem; acrescentar, remover e mover linhas e colunas SHALL levar a imagem junto com a célula.

#### Scenario: Remover a coluna do logo
- **WHEN** o admin remove a segunda coluna de uma tabela cujas células dessa coluna têm imagem
- **THEN** as imagens saem junto com a coluna e as outras células continuam com as suas

#### Scenario: Colar planilha numa tabela com logos
- **WHEN** o admin cola uma planilha na área de colar de um item cuja tabela tinha logos
- **THEN** a tabela passa a ter só o texto colado, e "Desfazer colagem" traz de volta a tabela com os logos

### Requirement: Envio de imagem do painel
O painel SHALL oferecer um envio de imagem que grava o arquivo na hora em que o admin o escolhe e devolve o nome gravado, aceitando só JPG, PNG, WebP ou GIF de até 5 MB, só para usuário logado no painel do site e com o token do formulário; o arquivo recusado SHALL ter o motivo mostrado ao admin.

#### Scenario: Arquivo que não é imagem
- **WHEN** o admin escolhe um PDF para a célula
- **THEN** nada é gravado e o painel mostra que só são aceitas imagens JPG, PNG, WebP ou GIF

#### Scenario: Sem login
- **WHEN** o endereço de envio recebe um arquivo sem sessão do painel
- **THEN** nada é gravado e a resposta redireciona para o login

#### Scenario: Token inválido
- **WHEN** o envio chega com o token do formulário ausente ou errado
- **THEN** nada é gravado e a resposta é 403
