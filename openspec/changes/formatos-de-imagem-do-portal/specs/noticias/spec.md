## ADDED Requirements

### Requirement: Formatos de imagem aceitos no portal
O envio da capa da notícia (no `/admin` e na área do jornalista) e da foto do jornalista (no cadastro e no perfil) SHALL aceitar só arquivos com extensão `.jpg`, `.jpeg` ou `.png`, em maiúsculas ou minúsculas, e conteúdo JPEG ou PNG. Qualquer outro arquivo SHALL voltar com "A imagem precisa ser JPG ou PNG." e MUST NOT ser gravado.

#### Scenario: Capa em AVIF
- **WHEN** a jornalista envia uma capa em AVIF
- **THEN** a notícia não é gravada e o formulário volta com "A imagem precisa ser JPG ou PNG."

#### Scenario: Capa em WebP
- **WHEN** a jornalista envia uma capa em WebP
- **THEN** a notícia não é gravada e o formulário volta com "A imagem precisa ser JPG ou PNG."

#### Scenario: Extensão em maiúsculas
- **WHEN** a jornalista envia a capa "FOTO.JPG" com conteúdo JPEG
- **THEN** a capa é gravada e a miniatura da lista é gerada

#### Scenario: Extensão trocada
- **WHEN** a jornalista envia um arquivo AVIF renomeado para "foto.jpg"
- **THEN** a notícia não é gravada e o formulário volta com "A imagem precisa ser JPG ou PNG."

### Requirement: Imagem gravada que não vira miniatura
No tema `noticias`, quando a capa de uma notícia ou a foto de um jornalista está num formato que o servidor não consegue abrir, as listas de notícias, "Mais lidas", "Leia também" e a página do autor SHALL mostrar o arquivo original no lugar da miniatura, no mesmo tamanho e recorte, e MUST NOT apontar para o endereço da miniatura.

#### Scenario: Capa AVIF já gravada
- **WHEN** uma notícia tem capa AVIF gravada antes desta mudança e alguém abre a página do autor dela
- **THEN** o item mostra `/uploads/article/<arquivo>.avif`, sem erro 500 e sem imagem quebrada
