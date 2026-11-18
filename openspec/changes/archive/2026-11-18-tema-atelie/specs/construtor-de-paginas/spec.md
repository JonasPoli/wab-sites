# Spec Delta

## ADDED Requirements

### Requirement: Home sem página montada no tema atelie
Num site com tema `atelie` sem página home definida, `/` SHALL mostrar o primeiro banner ativo do site como destaque (título, subtítulo, botão e imagem), o texto "sobre" do site quando houver e, com o blog ligado, as três publicações mais recentes; sem banner, mostra o nome do site como título.

#### Scenario: Site recém-criado
- **WHEN** um site novo com tema `atelie`, sem banners e sem páginas, recebe `/`
- **THEN** a resposta é 200 com o nome do site entre o cabeçalho e o rodapé do tema

### Requirement: Largura dos blocos no tema atelie
No tema `atelie`, os blocos `banner`, `statement` e `cta` SHALL ocupar a largura inteira da tela, e os demais SHALL ficar na coluna central de até 1200 px; uma seção só com blocos de largura inteira não tem respiro vertical próprio.

#### Scenario: Faixa de chamada no fim da página
- **WHEN** a última seção da página tem só um bloco `cta`
- **THEN** a faixa encosta nas laterais da tela e no rodapé, sem faixa clara entre eles
