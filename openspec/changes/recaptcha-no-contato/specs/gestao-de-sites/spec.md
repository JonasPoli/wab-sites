# Spec Delta

## ADDED Requirements

### Requirement: Chaves do reCAPTCHA do tenant
O cadastro e a edição do tenant no `/superadmin` SHALL ter os campos site key e secret key do reCAPTCHA v3. A secret key MUST NOT ser exibida depois de gravada: o campo mostra só se há chave, campo em branco mantém a chave atual, e uma opção explícita remove as duas chaves. O `/admin` do site MUST NOT mostrar nem editar essas chaves.

#### Scenario: Editar o tenant sem mexer na secret key
- **WHEN** o SuperAdmin abre a edição de um tenant com chaves, muda só a cor primária e salva
- **THEN** a secret key continua a mesma e a página de edição não mostra o valor dela

#### Scenario: Remover as chaves
- **WHEN** o SuperAdmin marca a opção de remover as chaves do reCAPTCHA e salva
- **THEN** o tenant fica sem as duas chaves e o contato do site volta a funcionar sem reCAPTCHA

### Requirement: Secret key fora da exportação
A exportação do tenant em ZIP MUST NOT incluir a secret key do reCAPTCHA; a site key SHALL ir no `metadata.json`. A importação SHALL criar o tenant com a site key do arquivo e sem secret key.

#### Scenario: Exportar e importar um site com chaves
- **WHEN** o SuperAdmin exporta um tenant com as duas chaves e importa o ZIP como um tenant novo
- **THEN** o `metadata.json` não tem a secret key e o tenant importado fica sem secret key, ou seja, sem reCAPTCHA até cadastrar a chave

### Requirement: Sites sem reCAPTCHA no painel
O painel do `/superadmin` SHALL indicar, na lista de tenants, quais sites estão sem as duas chaves do reCAPTCHA.

#### Scenario: Depois do deploy
- **WHEN** o SuperAdmin abre o `/superadmin` antes de cadastrar qualquer chave
- **THEN** todos os tenants aparecem marcados como sem reCAPTCHA
