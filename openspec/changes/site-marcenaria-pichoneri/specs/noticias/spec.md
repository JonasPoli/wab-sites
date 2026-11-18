# Spec Delta

## ADDED Requirements

### Requirement: Capa na lista de notícias do painel
A lista de notícias (ou de posts do blog) do `/admin` SHALL mostrar a miniatura da capa de cada notícia que tem capa, e um marcador de "sem capa" nas que não têm.

#### Scenario: Notícia com e sem capa
- **WHEN** o admin abre `/admin/noticias` com uma notícia com capa e outra sem
- **THEN** a primeira linha mostra a miniatura da capa e a segunda, o marcador "Sem capa"
