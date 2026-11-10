## MODIFIED Requirements

### Requirement: Cadastro do jornalista candidato
No tema `noticias`, `/jornalista/cadastro` SHALL oferecer ao público o cadastro com nome, e-mail, senha (mínimo de 8 caracteres) e confirmação, WhatsApp, endereço, estado (uma das 27 UFs), casa espírita que frequenta, registro profissional, veículo, cidade, minibio (até 600 caracteres), foto e aceite obrigatório do uso dos dados. Nome, e-mail, senha, WhatsApp, endereço, estado e aceite SHALL ser obrigatórios; o WhatsApp SHALL ter 10 ou 11 dígitos, desconsiderada a pontuação. O envio SHALL exigir token CSRF e campo isca vazio e criar, no site do domínio, o usuário (login = e-mail) e o autor como pendentes.

#### Scenario: Cadastro aceito
- **WHEN** uma jornalista envia o formulário com e-mail novo, WhatsApp "(16) 99999-1234", endereço, estado "SP" e casa espírita
- **THEN** o usuário e o autor são gravados como pendentes, com o WhatsApp, o endereço, o estado e a casa espírita informados

#### Scenario: WhatsApp inválido
- **WHEN** o candidato informa o WhatsApp "1234"
- **THEN** nada é gravado e o formulário volta com "Informe o WhatsApp com DDD."

#### Scenario: Estado fora da lista
- **WHEN** o envio chega com o estado "XX"
- **THEN** nada é gravado e o formulário volta com "Escolha o estado."

#### Scenario: E-mail já usado
- **WHEN** o candidato informa um e-mail que já é login ou e-mail de qualquer usuário da base
- **THEN** nada é gravado e o formulário volta com "Este e-mail já está cadastrado."

#### Scenario: Robô preenche o campo isca
- **WHEN** o envio chega com o campo isca preenchido
- **THEN** nada é gravado e a página mostra a mesma mensagem de cadastro recebido

#### Scenario: Site com outro tema
- **WHEN** alguém abre `/jornalista/cadastro` num site com tema cetec
- **THEN** a resposta é 404

### Requirement: Perfil do jornalista
Em `/jornalista/perfil`, o jornalista aprovado SHALL editar nome, WhatsApp, endereço, estado, casa espírita, registro profissional, veículo, cidade, minibio e foto, com as mesmas regras do cadastro para WhatsApp, endereço e estado, e trocar a senha informando a atual; o e-mail, o slug e a situação não mudam por ali.

#### Scenario: Senha atual errada
- **WHEN** o jornalista tenta trocar a senha informando a senha atual errada
- **THEN** a senha não muda e o formulário volta com "Senha atual incorreta."

#### Scenario: Troca do estado
- **WHEN** o jornalista muda o estado de "SP" para "MG" e salva
- **THEN** o perfil passa a mostrar "MG"

## ADDED Requirements

### Requirement: Dados privados do jornalista
A ficha do jornalista no `/admin/jornalistas` SHALL mostrar WhatsApp, endereço, estado e casa espírita; a página pública do autor (`/autor/{slug}`) MUST NOT mostrar nenhum desses dados.

#### Scenario: Página pública do autor
- **WHEN** alguém abre a página pública de um jornalista com endereço e WhatsApp gravados
- **THEN** a página não mostra o endereço, o estado, o WhatsApp nem a casa espírita
