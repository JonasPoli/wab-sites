## MODIFIED Requirements

### Requirement: Exportação da newsletter
O `/admin/newsletter/export.csv` SHALL gerar um CSV `newsletter.csv` com os inscritos do tenant do domínio: cabeçalho `id,email,data` e uma linha por inscrito com o id, o e-mail e a data de inscrição no formato `AAAA-MM-DD HH:MM`, com as células escapadas como CSV. O botão "Exportar CSV" do `/admin/newsletter` SHALL baixar esse arquivo.

#### Scenario: Exportar com inscritos
- **WHEN** o tenant tem 3 inscritos e o admin pede a exportação
- **THEN** o CSV vem com o cabeçalho e 3 linhas com id, e-mail e data de inscrição

#### Scenario: Exportar sem inscritos
- **WHEN** o tenant não tem inscritos
- **THEN** o CSV vem só com o cabeçalho

#### Scenario: Inscritos de outro site
- **WHEN** o admin do tenant 1 exporta a newsletter em `marapossi.com.br`
- **THEN** os inscritos do tenant 3 não aparecem no arquivo

#### Scenario: E-mail que começa com sinal de fórmula
- **WHEN** um inscrito tem o e-mail `=teste@exemplo.com`
- **THEN** a célula sai como `'=teste@exemplo.com`, para a planilha não executar como fórmula

#### Scenario: Botão do painel
- **WHEN** o admin clica em "Exportar CSV" no `/admin/newsletter`
- **THEN** o navegador baixa o `newsletter.csv` gerado por `/admin/newsletter/export.csv`
