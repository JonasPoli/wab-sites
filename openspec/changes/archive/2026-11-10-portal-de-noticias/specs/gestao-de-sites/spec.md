## MODIFIED Requirements

### Requirement: Usuários pelo SuperAdmin
O sistema SHALL cadastrar usuários com username, nome, e-mail, `workGroup` (0 admin, 1 editor, 2 revisor, 3 jornalista) e tenant; sem tenant e com `workGroup` 0, o usuário é SuperAdmin; a senha só muda quando um valor é digitado. O formulário SHALL vir com o `workGroup` atual do usuário marcado e recusar valores fora de 0 a 3; criar um jornalista por ali não cria o perfil de autor.

#### Scenario: Editar sem trocar a senha
- **WHEN** o SuperAdmin edita um usuário e deixa a senha em branco
- **THEN** a senha atual é mantida

#### Scenario: Trocar a senha de um jornalista
- **WHEN** o SuperAdmin edita um jornalista (`workGroup` 3), digita uma senha nova e salva
- **THEN** a senha muda e o usuário continua com `workGroup` 3, sem ganhar acesso ao `/admin`
