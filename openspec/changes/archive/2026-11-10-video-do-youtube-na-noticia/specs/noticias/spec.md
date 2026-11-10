## ADDED Requirements

### Requirement: Campo de vídeo no cadastro da notícia
O formulário de notícia da área do jornalista e do `/admin` SHALL ter o campo opcional "Vídeo do YouTube (link)", com um vídeo por notícia; campo vazio SHALL deixar a notícia sem vídeo.

#### Scenario: Tirar o vídeo
- **WHEN** a jornalista apaga o link do vídeo de uma notícia e salva
- **THEN** a notícia fica sem vídeo

### Requirement: Links de vídeo aceitos
O campo de vídeo SHALL aceitar links do YouTube nos formatos `youtube.com/watch?v=`, `youtu.be/`, `youtube.com/shorts/`, `youtube.com/live/` e `youtube.com/embed/`, com ou sem `www.` e `m.`, e gravar o link como `https://www.youtube.com/watch?v=<id>`. Link de outro site ou sem o id do vídeo SHALL voltar com "Informe um link de vídeo do YouTube (youtube.com ou youtu.be)." sem gravar a notícia.

#### Scenario: Link curto do YouTube
- **WHEN** a jornalista cria uma notícia com o vídeo "https://youtu.be/dQw4w9WgXcQ?si=abc"
- **THEN** a notícia é gravada com o vídeo "https://www.youtube.com/watch?v=dQw4w9WgXcQ"

#### Scenario: Link de outro site
- **WHEN** o vídeo informado é "https://vimeo.com/123456"
- **THEN** a notícia não é gravada e o formulário volta com "Informe um link de vídeo do YouTube (youtube.com ou youtu.be)."

### Requirement: Vídeo no fim da notícia
No tema `noticias`, a página da notícia com vídeo SHALL mostrar o player do YouTube no fim do texto completo, antes da data final, com a largura da coluna do texto e proporção 16:9, carregado de `www.youtube-nocookie.com/embed/<id>` só quando chega perto da tela e com título acessível "Vídeo: <título da notícia>". Notícia sem vídeo SHALL não mostrar área de vídeo.

#### Scenario: Notícia com vídeo
- **WHEN** alguém abre uma notícia publicada com o vídeo "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
- **THEN** depois do último parágrafo do texto aparece o player de `https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ`

#### Scenario: Notícia sem vídeo
- **WHEN** alguém abre uma notícia sem vídeo
- **THEN** a página não tem player nem espaço reservado para vídeo

### Requirement: Vídeo na exportação e na importação
A exportação do site SHALL incluir o link do vídeo de cada notícia, e a importação SHALL restaurá-lo; arquivo exportado antes deste change SHALL ser importado com as notícias sem vídeo.

#### Scenario: Exportar e importar notícia com vídeo
- **WHEN** um site com uma notícia com vídeo é exportado e importado em outro domínio
- **THEN** a notícia importada tem o mesmo link de vídeo
