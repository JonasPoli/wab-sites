# Spec Delta

## ADDED Requirements

### Requirement: Prévia do bloco no painel
A criação e a edição de qualquer tipo de bloco no `/admin` SHALL mostrar, ao lado do formulário, uma prévia do bloco desenhada pelos templates do tema do site, dentro de uma seção com o mesmo fundo da seção do bloco, atualizada enquanto o formulário é preenchido, sem gravar nada.

#### Scenario: Texto digitado
- **WHEN** o admin digita "Versatilidade para diferentes espaços" no título de um bloco blurbs4 novo
- **THEN** a prévia mostra esse título no lugar e no estilo em que ele sairá no site, sem que o bloco seja criado

#### Scenario: Mudança no template do tema
- **WHEN** o parcial do bloco no tema do site é alterado
- **THEN** a prévia passa a mostrar o desenho novo, sem nenhuma alteração na prévia

#### Scenario: Edição sem salvar
- **WHEN** o admin muda o texto de um bloco existente e sai da tela sem salvar
- **THEN** o site continua com o texto antigo

### Requirement: Campo vazio na prévia
Na prévia, campo de texto vazio SHALL aparecer com o nome público do campo (o rótulo do formulário, precedido do nome do grupo quando o campo se repete, como "Item 2 — Título"); imagem vazia SHALL aparecer como uma imagem de exemplo; lista sem itens (depoimentos, logos, equipe, fotos) SHALL aparecer com itens de exemplo.

#### Scenario: Pré-título vazio
- **WHEN** o pré-título do bloco está vazio
- **THEN** a prévia mostra "Pré-título" onde o pré-título sairá

#### Scenario: Depoimentos sem nenhum item
- **WHEN** um bloco de depoimentos ainda não tem depoimento
- **THEN** a prévia mostra um depoimento de exemplo com "Nome", "Cargo / Função" e "Depoimento"

### Requirement: Arquivo escolhido na prévia
Uma imagem escolhida no formulário e ainda não salva SHALL aparecer na prévia pelo arquivo do computador do admin, sem ser enviada ao servidor antes de salvar.

#### Scenario: Foto nova do destaque
- **WHEN** o admin escolhe a foto de um bloco "Destaque com foto"
- **THEN** a prévia mostra essa foto no lugar da imagem de exemplo

### Requirement: Prévia restrita ao site
A prévia SHALL só aceitar seção e bloco do site do domínio e só para quem acessa o `/admin` desse site.

#### Scenario: Seção de outro site
- **WHEN** o pedido de prévia traz o id de uma seção de outro site
- **THEN** a resposta é 404
