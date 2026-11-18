# Spec Delta

## ADDED Requirements

### Requirement: Interruptor do blog no cadastro do site
A edição do site no `/superadmin` SHALL ter o interruptor "Blog", desligado em todo site novo e em todos os que já existem; a exportação SHALL levar o valor e a importação SHALL recriá-lo, tratando como desligado o ZIP que não o traz.

#### Scenario: Site existente depois da atualização
- **WHEN** a atualização é aplicada num servidor com sites já cadastrados
- **THEN** todos continuam com o blog desligado e sem nenhuma página nova

#### Scenario: ZIP antigo
- **WHEN** um ZIP exportado antes desta mudança é importado
- **THEN** o site importado fica com o blog desligado
