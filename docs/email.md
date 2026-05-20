# Sistema de E-mails e Integração com Wmailer no Symfony

Este documento detalha o funcionamento, a instalação, a configuração e o uso do sistema de envio de e-mails implementado no projeto **Wab 2024**, utilizando o componente nativo **Symfony Mailer** e o transporte customizado **wmailer-transport** (Wab Ninjas).

---

## 1. Como Funciona o Sistema de E-mails do Site

O envio de e-mails neste projeto é construído em cima do ecossistema moderno do Symfony, integrando três pilares fundamentais:

```mermaid
flowchart TD
    A[Controller / Service] -->|Cria TemplatedEmail| B[Symfony Mailer]
    B -->|Utiliza| C[WabTransportFactory]
    C -->|Instancia| D[WabApiTransport]
    D -->|Renderiza| E[Twig Environment]
    D -->|Envia payload JSON via HTTP POST| F[API Wmailer: wmailer.wab.com.br]
```

1. **Definição da Mensagem**: O desenvolvedor utiliza a classe `TemplatedEmail` (da extensão Twig Mime do Symfony) para estruturar o e-mail, passando metadados (remetente, destinatário, assunto) e associando um template Twig específico junto ao seu contexto de dados.
2. **Despacho**: O serviço `MailerInterface` é injetado no Controller ou Service. Quando `$mailer->send($email)` é invocado, o Symfony delega a entrega da mensagem para o Transport configurado no DSN.
3. **Transporte Customizado (Wmailer)**: O DSN resolve para o transporte customizado `WabApiTransport` (provido pelo pacote `wab-ninjas/wmailer-transport`). 
4. **Renderização e Envio**:
   * O `WabApiTransport` recebe a mensagem e invoca o `BodyRenderer` do Twig para compilar os templates HTML com as variáveis injetadas.
   * Constrói um payload JSON contendo o token de API (`key`), remetente (`mailFrom`), destinatário(s) (`mailTo`), assunto (`subject`), corpo renderizado (`body`) e anexos em base64 (`attachments`).
   * Faz uma requisição HTTP POST assíncrona (via HTTP Client do Symfony) para a API oficial do Wmailer em `https://wmailer.wab.com.br/v1/send`.

---

## 2. Como Está Configurado Atualmente

A configuração do sistema está distribuída em variáveis de ambiente e arquivos de configuração do Symfony.

### A. Parâmetros e Variáveis de Ambiente (`.env` e `.env.local`)
No arquivo `.env` e replicado no `.env.local`, existem as seguintes chaves dedicadas ao fluxo de e-mails:

```ini
# Configuração do Symfony Mailer DSN
MAILER_DSN=wmailer+api://SEU_TOKEN_DE_API@default

# Configurações de Origem e Destino do Projeto
EMAIL_FROM="Site Wab <contato@wab.com.br>"
EMAIL_CONTACT_TO="destino@wab.com.br"
```

> [!NOTE]
> * **`MAILER_DSN`**: Define o protocolo `wmailer+api://` seguido do Token de API que autentica o projeto na plataforma Wmailer.
> * **`EMAIL_FROM`**: É o endereço de e-mail padrão que aparecerá como remetente da mensagem.
> * **`EMAIL_CONTACT_TO`**: É o endereço de e-mail do administrador do site, responsável por receber as mensagens de contato e currículos preenchidos.

### B. Definição nos Serviços (`config/services.yaml`)
Para que o `EMAIL_FROM` e o `EMAIL_CONTACT_TO` possam ser facilmente injetados nos Controllers e Services sem "hardcodar" os valores, eles são mapeados como parâmetros globais do Symfony container:

```yaml
parameters:
    emailContactTo: '%env(resolve:EMAIL_CONTACT_TO)%'
    emailFrom: '%env(resolve:EMAIL_FROM)%'
```

Isso nos permite recuperá-los de forma dinâmica no Controller usando `$parameters->get('emailFrom')` ou injetando-os via injeção de dependências no construtor de um serviço através de binding de parâmetros.

### C. Integração do Mailer (`config/packages/mailer.yaml`)
O Symfony lê o DSN diretamente da variável de ambiente:

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

---

## 3. Como Foi Instalado

O sistema de e-mail foi integrado através da união do componente oficial do Symfony Mailer com o ecossistema privado da Wab Ninjas:

1. **Instalação do Componente Nativo de E-mail**:
   Instalado via Composer com o comando:
   ```bash
   composer require symfony/mailer
   ```
   *Este comando adiciona a biblioteca `symfony/mailer` e `symfony/mime` para manusear e-mails complexos, gerando o arquivo `config/packages/mailer.yaml`.*

2. **Instalação do Transporte Customizado (Wmailer)**:
   Foi instalado o pacote privado da Wab Ninjas como dependência do projeto no `composer.json`:
   ```json
   "require": {
       ...
       "wab-ninjas/wmailer-transport": "*"
   }
   ```
   Este pacote fornece o suporte ao esquema de transporte de rede `wmailer+api://` para a API da Wab.

3. **Registro do Bundle no Symfony**:
   Para que o Symfony reconheça o novo tipo de DSN, o bundle foi registrado em `config/bundles.php`:
   ```php
   return [
       ...
       WabNinjas\WmailerTransport\WmailerTransportBundle::class => ['all' => true],
   ];
   ```
   *O `WmailerTransportBundle` injeta a classe `WabTransportFactory` marcada com a tag `mailer.transport_factory` no Container de Serviços do Symfony, permitindo que a fábrica intercepte esquemas de DSN começados com `wmailer` ou `wmailer+api`.*

---

## 4. O Que Mudou na Configuração de Mensagens do Symfony (Represa ou Envia?)

Uma dúvida comum em sistemas Symfony é se o e-mail é enviado na hora (de forma síncrona, travando a navegação do usuário até concluir a requisição de e-mail) ou se ele é **represado** (enviado em background de forma assíncrona).

No projeto atual, **o e-mail é enviado imediatamente (Síncrono)**. Veja por quê:

No arquivo `config/packages/messenger.yaml`, a diretiva que dita o envio assíncrono de e-mails está **comentada**:

```yaml
framework:
    messenger:
        ...
        routing:
            # Symfony\Component\Mailer\Messenger\SendEmailMessage: async
            # Symfony\Component\Notifier\Message\ChatMessage: async
            # Symfony\Component\Notifier\Message\SmsMessage: async
```

### O Comportamento Atual (Síncrono - Linha comentada):
* Quando `$mailer->send($email)` é invocado, o PHP realiza a requisição HTTP POST para a API do Wmailer de forma síncrona.
* O fluxo da aplicação fica travado aguardando o retorno da API do Wmailer.
* **Prós**: O usuário recebe feedback imediato se o e-mail realmente pôde ser enviado ou se falhou na entrega à API.
* **Contras**: A requisição web demora mais tempo para completar (o tempo da latência da rede e processamento da API de e-mail).

### Como Represar para Envio Assíncrono (Recomendado para Produção):
Para que o envio não trave a tela do usuário final, você pode fazer com que o Symfony represe o envio no banco de dados (ou Redis) usando o **Symfony Messenger**.

1. **Ativar o Roteamento Assíncrono**:
   Descomente a linha correspondente no arquivo `config/packages/messenger.yaml`:
   ```yaml
   framework:
       messenger:
           routing:
               Symfony\Component\Mailer\Messenger\SendEmailMessage: async
   ```

2. **Funcionamento do Represamento**:
   * O DSN do transport `async` está definido no `.env` como `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0`.
   * Quando o Controller disparar `$mailer->send()`, o e-mail será serializado e gravado na tabela do banco de dados do Doctrine (`messenger_messages`) como uma tarefa pendente.
   * O Controller responderá imediatamente ao usuário final com velocidade instantânea.

3. **Consumo da Fila (Worker)**:
   Com os e-mails represados no banco de dados, é obrigatório ter um processo em background no servidor rodando para ler essa fila e fazer os disparos de fato:
   ```bash
   php bin/console messenger:consume async
   ```
   *(Em servidores de produção, esse comando geralmente é gerenciado por um monitor de processos como o **Supervisor** ou o **systemd** para garantir que esteja sempre rodando).*

---

## 5. Tutorial Completo: Como Configurar o Sistema de E-mail do Zero no Symfony

Se você precisar configurar um novo projeto Symfony para usar o Mailer ou ajustar um transporte do zero, siga o guia abaixo:

### Passo 1: Instale as Dependências
Abra o terminal na raiz do projeto e instale o Mailer:
```bash
composer require symfony/mailer
```

### Passo 2: Configure o DSN do Mailer
No arquivo `.env.local` da raiz, defina como o e-mail será enviado. O Symfony Mailer suporta múltiplos backends nativos e externos:

* **Para usar o Wmailer (Wab Ninjas)**:
  ```ini
  MAILER_DSN=wmailer+api://SUA_API_KEY@default
  ```
* **Para usar SMTP Genérico (com SSL/TLS)**:
  ```ini
  MAILER_DSN=smtp://usuario:senha@smtp.provedor.com:465
  ```
* **Para usar um Provedor Oficial (ex: SendGrid, Mailgun, Amazon SES)**:
  Instale o adapter específico (ex: `composer require symfony/sendgrid-mailer`) e use:
  ```ini
  MAILER_DSN=sendgrid://KEY@default
  ```
* **Para ambiente de Desenvolvimento Local (Não enviar e-mails de verdade - Null/Discard)**:
  ```ini
  MAILER_DSN=null://null
  ```
* **Para capturar e-mails localmente com Mailpit / MailHog**:
  ```ini
  MAILER_DSN=smtp://localhost:1025
  ```

---

## 6. Tutorial Prático: Como Enviar E-mails no Código

O Symfony Mailer provê duas classes principais de e-mail: `Symfony\Component\Mime\Email` (para e-mails simples/texto) e `Symfony\Bridge\Twig\Mime\TemplatedEmail` (para e-mails complexos estilizados em Twig).

### A. Envio de E-mail Simples (Texto Plano ou HTML Rígido)
Excelente para alertas rápidos ou logs técnicos.

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;

public function enviarAlerta(MailerInterface $mailer): Response
{
    $email = (new Email())
        ->from('suporte@wab.com.br')
        ->to('admin@wab.com.br')
        ->subject('Alerta do Sistema')
        ->text('Ocorreu um acesso incomum no painel administrativo.')
        ->html('<p>Ocorreu um <strong>acesso incomum</strong> no painel administrativo.</p>');

    $mailer->send($email);

    return new Response('Alerta enviado!');
}
```

### B. Envio de E-mail Rico com Templates Twig (`TemplatedEmail`)
Esta é a abordagem ideal para formulários de contato, boletins informativos ou notificações transacionais do site, separando o design (HTML) da lógica (PHP).

#### 1. Escrevendo o Controller
Injete o `MailerInterface` e monte a estrutura. Veja como o projeto **Wab 2024** trata isso no `ContactController.php` para o envio de currículos:

```php
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/contato-enviar', name: 'app_contato_post', methods: ['POST'])]
public function enviarContato(Request $request, MailerInterface $mailer, ParameterBagInterface $parameters): Response
{
    // Captura dados do formulário
    $data = $request->request->all();

    try {
        $email = (new TemplatedEmail())
            // Injeta parâmetros definidos no config/services.yaml
            ->from($parameters->get('emailFrom'))
            ->to($parameters->get('emailContactTo'))
            ->subject('Contato Recebido pelo Site')
            
            // Aponta para o arquivo de template twig
            ->htmlTemplate('email/contact.html.twig')
            
            // Injeta dados para o template Twig ler
            ->context([
                'data' => $data,
            ]);

        // Envia (síncrono ou assíncrono dependendo do messenger.yaml)
        $mailer->send($email);

        $this->addFlash('success', 'Mensagem enviada com sucesso!');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Falha ao enviar e-mail: ' . $e->getMessage());
    }

    return $this->redirectToRoute('pub_home');
}
```

#### 2. Escrevendo o Template Twig (`templates/email/contact.html.twig`)
No template de e-mail, você pode desenhar uma estrutura corporativa elegante utilizando HTML e tabelas (essenciais para compatibilidade com clientes de e-mail como Outlook e Gmail).

Os dados injetados via `context` ficam disponíveis na variável global correspondente:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contato Recebido</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="background: white; border: 1px solid #ddd; padding: 20px;">
        <tr>
            <td style="border-bottom: 2px solid #0056b3; padding-bottom: 10px;">
                <h2>Novo Contato via Site</h2>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <p><strong>Nome:</strong> {{ data.nome }}</p>
                <p><strong>E-mail:</strong> {{ data.email }}</p>
                <p><strong>Telefone:</strong> {{ data.telefone }}</p>
                <p><strong>Mensagem:</strong></p>
                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0056b3;">
                    {{ data.mensagem | nl2br }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
```

---

## 7. Como Tratar Anexos nos E-mails

Caso você precise enviar um e-mail contendo um anexo (por exemplo, um PDF de currículo ou uma imagem de comprovante):

O Symfony Mailer simplifica isso usando o método `attachFromPath` ou `attach`:

```php
$email = (new TemplatedEmail())
    ->from('remetente@site.com')
    ->to('destino@site.com')
    ->subject('Inscrição da Vaga')
    ->htmlTemplate('email/curriculum.html.twig')
    ->context(['data' => $data])
    // Adiciona anexo a partir de um arquivo físico no servidor
    ->attachFromPath('/caminho/no/servidor/curriculo.pdf', 'Nome_Exibicao.pdf');
```

### Como o Wmailer Transport Trata Anexos de Forma Transparente:
Se você usar o transporte da Wab, a classe `WabApiTransport` intercepta o e-mail, percorre todos os anexos configurados através do método `$email->getAttachments()`, converte-os automaticamente em Base64 e os injeta no payload JSON sob a chave `attachments`:

```php
private function prepareAttachments(Email $email): array
{
    $attachments = [];
    foreach ($email->getAttachments() as $attachment) {
        $attachments[] = [
            'name' => $attachment->getFilename(),
            'content_type' => $attachment->getContentType(),
            'data' => base64_encode($attachment->getBody()),
        ];
    }
    return $attachments;
}
```

Isso garante que mesmo anexos complexos funcionem nativamente através da API HTTP do Wmailer sem precisar mudar nada no seu código PHP do Controller.
