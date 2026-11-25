## ADDED Requirements

### Requirement: Botão flutuante na edição do tenant
A edição do tenant no `/superadmin` SHALL ter o grupo "Botão flutuante" com o tipo (Nenhum, WhatsApp ou Formulário) e, no Formulário, a página de destino (as páginas do tenant editado), o texto do botão e o ícone, escolhido no seletor de ícones do bloco Blurbs4. Os campos do Formulário SHALL ficar visíveis só com esse tipo marcado.

#### Scenario: Escolher o formulário
- **WHEN** o SuperAdmin marca Formulário, escolhe a página "Contato", digita "Fale com a gente", escolhe o ícone de envelope no seletor e salva
- **THEN** o tenant passa a ter o tipo Formulário com essa página, esse texto e o ícone `fa-solid fa-envelope`, e a tela de edição volta com esses valores

#### Scenario: Trocar para Nenhum
- **WHEN** o SuperAdmin marca Nenhum e salva
- **THEN** o site deixa de mostrar o botão flutuante, e a página, o texto e o ícone gravados antes continuam guardados para uma nova troca

### Requirement: Gravação do botão flutuante
Ao salvar a edição do tenant, o sistema SHALL aceitar só os tipos Nenhum, WhatsApp e Formulário, gravar como destino só uma página do tenant editado (outra fica sem destino), cortar o texto em 40 caracteres sem espaços nas pontas e manter a escolha anterior quando o envio não traz o tipo.

#### Scenario: Página de outro tenant
- **WHEN** o POST de edição do tenant 5 traz como página de destino o id de uma página do tenant 1
- **THEN** o tenant 5 fica com o tipo escolhido e sem página de destino, e o tenant 1 não muda

#### Scenario: Tipo desconhecido
- **WHEN** o POST traz o tipo `banner`
- **THEN** o tipo gravado é WhatsApp

#### Scenario: Cadastro de tenant novo
- **WHEN** o SuperAdmin cadastra um tenant novo pelo `/superadmin`
- **THEN** o tenant novo começa com o tipo WhatsApp

### Requirement: Botão flutuante na exportação e na importação
A exportação do tenant SHALL levar o tipo, o texto, o ícone e a página de destino do botão flutuante, e a importação SHALL gravá-los no tenant novo com a página de destino ligada à página importada correspondente; sem essa página no ZIP, o tenant importado fica sem destino.

#### Scenario: Exportar e importar com formulário
- **WHEN** um site com botão de formulário para a página "Contato" é exportado e importado com outro domínio
- **THEN** o site importado tem o mesmo tipo, texto e ícone, e o destino é a página "Contato" do site importado
