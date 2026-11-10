## ADDED Requirements

### Requirement: Papel de jornalista
O usuário com tenant e `workGroup` 3 (jornalista) SHALL receber ROLE_JOURNALIST, sem ROLE_ADMIN nem ROLE_EDITOR. `/jornalista` SHALL exigir ROLE_JOURNALIST, exceto `/jornalista/cadastro`, que é público; o `/admin` continua exigindo ROLE_ADMIN, e o jornalista não abre nenhuma página dele.

#### Scenario: Jornalista no painel
- **WHEN** um jornalista aprovado abre `/admin/page` digitando o endereço
- **THEN** a resposta é 403

#### Scenario: Visitante na área do jornalista
- **WHEN** um visitante sem login abre `/jornalista`
- **THEN** ele é levado para a página de login, enquanto `/jornalista/cadastro` abre sem login
