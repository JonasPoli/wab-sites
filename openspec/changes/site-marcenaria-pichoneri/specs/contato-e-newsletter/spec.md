# Spec Delta

## ADDED Requirements

### Requirement: Aviso do envio em janela
Depois de enviar o formulário de contato ou de newsletter, o site SHALL mostrar a mensagem do resultado numa janela no centro da tela, com o fundo escurecido, um título ("Obrigado!" no sucesso, "Atenção" no erro) e o botão OK, que fecha a janela; Esc e o clique fora da caixa também fecham. Vale em todos os temas públicos.

#### Scenario: Contato enviado
- **WHEN** o visitante envia o formulário de contato preenchido
- **THEN** a página volta com a janela "Obrigado!" e a mensagem "Mensagem enviada com sucesso!", com o fundo escurecido e o foco no botão OK

#### Scenario: E-mail inválido na newsletter
- **WHEN** o visitante envia a newsletter com um e-mail inválido
- **THEN** a janela "Atenção" mostra "E-mail inválido ou já inscrito." e o OK a fecha

#### Scenario: Sem JavaScript
- **WHEN** a página com o aviso é aberta com o JavaScript desligado
- **THEN** a mensagem aparece numa caixa no meio da tela, que o botão OK fecha
