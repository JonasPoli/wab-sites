# WAB Sites — Sistema Multi-Tenant de Construção de Sites

Plataforma multi-inquilino (multi-tenant) desenvolvida em **Symfony 7** e **PHP 8.3+** para criação, personalização e gerenciamento dinâmico de websites institucionais. O sistema permite que múltiplos sites operem de maneira isolada a partir de uma única instalação e banco de dados comum, resolvendo dinamicamente o branding, conteúdos, temas e permissões com base no domínio do request.

---

## 🚀 Arquitetura Multi-Tenant

O WAB Sites utiliza uma arquitetura **Single-Database (Shared Schema)** com isolamento lógico rígido e transparente. Os inquilinos (Tenants) são isolados e mapeados através do conceito de *Domain Resolution*.

```mermaid
sequenceDiagram
    autonumber
    Browser->>Kernel: Request HTTP (ex: Host: 127.0.0.1)
    Kernel->>TenantSubscriber: KernelEvents::REQUEST (Priority 100)
    TenantSubscriber->>TenantRepository: findByDomain("127.0.0.1")
    TenantRepository-->>TenantSubscriber: Entidade Tenant (ID: 1)
    TenantSubscriber->>TenantContext: setTenant(Tenant)
    TenantSubscriber->>EntityManager: Ativa Filtro SQL "tenant_filter" (tenant_id = 1)
    Kernel->>Controller: Match de Rota e Execução do Controller
    Controller->>Database: Queries via Doctrine ORM (Auto-injetando tenant_id = 1)
    Database-->>Controller: Entidades retornadas já isoladas por Tenant
    Controller-->>Browser: Resposta HTML customizada com cores e branding
```

### 1. Resolução do Tenant (`TenantSubscriber`)
No ciclo de vida de cada requisição Symfony, o listener `TenantSubscriber` atua de forma precoce (`KernelEvents::REQUEST`, prioridade 100):
1. Captura o host do cabeçalho da requisição através de `$request->getHost()`.
2. Consulta o banco buscando a entidade correspondente em `TenantRepository::findByDomain()`.
3. Caso o domínio não esteja cadastrado, retorna uma resposta amigável informando **"Domínio não configurado"** (HTTP 404).
4. Sendo localizado, injeta o objeto no `TenantContext` (um serviço com escopo de request) e ativa o filtro SQL do Doctrine.

### 2. Filtro Automático de Consultas (`TenantFilter`)
Para garantir segurança e impedir o vazamento acidental de dados entre tenants, o sistema utiliza o recurso de `SQLFilter` do Doctrine:
* Qualquer entidade que necessite de isolamento implementa a `TenantAwareInterface`.
* O `TenantFilter` intercepta dinamicamente as consultas SQL geradas pelo ORM e automaticamente adiciona a cláusula `AND tenant_id = :tenant_id` nas operações de `SELECT`, `UPDATE` ou `DELETE`.
* Isso isenta o desenvolvedor de ter que aplicar cláusulas `where(['tenant' => $tenant])` manualmente em cada consulta.

---

## 🎨 Temas e Branding Dinâmico

O WAB Sites oferece suporte a temas visuais customizáveis por tenant. O sistema inclui dois temas premium completos na pasta `templates/themes/`:
1. **Moderno (`moderno`)**: Design moderno dark-first (mas com seletor claro/escuro integrado e persistência via `localStorage`), baseado na fonte Google Fonts *Outfit* e estilização premium em vidro (glassmorphism).
2. **Nepe (`nepe`)**: Layout acadêmico e institucional, baseado na fonte *Inter*, com animações suaves de revelação na rolagem (`reveal-up`, `scale-in`).

### Injeção de Variáveis CSS (`TenantExtension`)
A extensão de Twig `TenantExtension` expõe globals e funções utilitárias que integram as preferências do banco com o visual público:
* **`tenant_css_vars()`**: Injeta dinamicamente no `<head>` um bloco `<style>` contendo propriedades personalizadas CSS (`--color-primary`, `--color-secondary` e suas versões RGB) baseadas nas cores de branding cadastradas no Tenant.
* **Global Variables**: Disponibiliza em 100% dos templates Twig as variáveis `currentTenant` (dados do site ativo), `headerCategories` (menu dinâmico), `footerPages` e `footerCategories` para renderização imediata do cabeçalho e rodapé.

---

## 🧱 Visual Builder: Seções & Blocos

Cada página no sistema é montada através de um modelo flexível e modular de **Seções** e **Blocos**, oferecendo máxima flexibilidade de layout.

```
Page (Página)
  ├── 📂 Category (Categoria associada)
  └── ⛓️ PageSection (Seção 1 - Ordem 0)
        ├── 🎨 Background (Cor, Gradiente, Imagem ou Vídeo)
        └── 🧩 PageBlock (Bloco 1 - Tipo: Imagem + Texto)
        └── 🧩 PageBlock (Bloco 2 - Tipo: Estatísticas)
```

### 1. Seções (`PageSection`)
As seções representam as faixas horizontais de conteúdo na página. Cada seção possui:
* **Propriedades de Background**: Permite definir o fundo da seção através do tipo de preenchimento (`bgType`):
  * **None**: Fundo estrutural do tema padrão.
  * **Color**: Cor CSS customizada (ex: `#1e293b`).
  * **Gradient**: Argumentos customizados para o `linear-gradient` do CSS.
  * **Image**: Upload de imagem via VichUploader com parametrização de opacidade (0 a 100%) e posicionamento.
  * **Video**: Upload de vídeo MP4 de fundo com suporte a autoplay, loop automático e overlay de cor por cima para contraste de leitura.

### 2. Blocos (`PageBlock`)
Os blocos de conteúdo são filhos diretos das seções e determinam o layout interno. O sistema conta com um dispatcher de templates (`_block.html.twig`) que delega a renderização para cada tipo de bloco:

| Tipo (`BlockType`) | Rótulo Admin | Descrição |
|---|---|---|
| `text_image` | Imagem + Texto | Exibe um bloco de conteúdo escrito acompanhado de imagem, com posição da imagem (esquerda/direita) e botão de ação (CTA) configurável. |
| `gallery` | Galeria de Imagens | Grade dinâmica com upload em massa de fotos e suporte a zoom/exibição em carrossel. |
| `newsletter` | Newsletter | Input para captura de e-mails integrada à base de dados. |
| `blurbs4` | Texto com 4 Blocos | Grid elegante com 4 cards contendo ícones FontAwesome personalizáveis, títulos e descrições. |
| `stats` | Estatísticas | Destaque de 4 números ou indicadores de performance. |
| `news_call` | Chamada para Notícia | Lista ou carrossel de páginas recentes recomendadas. |
| `map` | Mapa | Renderização embutida do Google Maps através de IFRAME configurado pelo admin. |
| `sub_categories` | Listar Subcategorias | Grid de subcategorias associadas a uma categoria pai. |
| `page_list` | Listar Páginas | Listagem automatizada de páginas pertencentes a uma categoria específica. |
| `testimonials` | Depoimentos | Lista de avaliações/depoimentos com notas em estrelas (1 a 5), nomes, avatares e cargos dos depoentes. |
| `partner_logos` | Logos de Parceiros | Linha horizontal de logotipos de patrocinadores ou parceiros institucionais com animações de hover. |
| `banner` | Banner | Slider de banners rotativos com imagens de fundo, títulos e botões de chamada de ação. |

> [!TIP]
> Os blocos flexibilizam as propriedades armazenando configurações estruturais complexas (ex: links CTA, títulos de cards) em uma única coluna do tipo **JSON** (`config`), mantendo a tabela limpa e a arquitetura altamente extensível.

---

## 🔑 Níveis de Acesso e Permissões (RBAC)

O sistema de gerenciamento de permissões é baseado no conceito de `workGroup` vinculado à entidade `User`. Existem 4 níveis principais de acesso no sistema:

1. **Super Administrador (Global)**
   * **Critério**: `tenant` nulo no cadastro do usuário e `workGroup` = 0.
   * **Perfil**: Possui papel `ROLE_SUPER_ADMIN`.
   * **Funções**: Acessa o painel `/superadmin` para criar/editar/excluir Tenants (configurar novos domínios), criar administradores de inquilinos e utilizar o recurso de **impersonificação (Switch User)** para simular o acesso de qualquer administrador local com um único clique.
2. **Administrador do Tenant**
   * **Critério**: `tenant` vinculado ao cadastro e `workGroup` = 0.
   * **Funções**: Gerencia configurações exclusivas do site (SEO, Favicon, Redes Sociais, dados de contato e página home do site), cria novos usuários editores/revisores locais, aprova publicações e realiza a gestão completa do banco de páginas, categorias, banners e contatos.
3. **Editor de Conteúdo**
   * **Critério**: `tenant` vinculado e `workGroup` = 1.
   * **Funções**: Acesso restrito ao painel administrativo. Cria e edita rascunhos de páginas, categorias e blocos, mas não possui permissão para editar configurações globais do inquilino ou gerenciar outros usuários.
4. **Revisor de Conteúdo**
   * **Critério**: `tenant` vinculado e `workGroup` = 2.
   * **Funções**: Acesso focado na moderação. Pode revisar rascunhos produzidos por editores e autorizar a publicação definitiva.

---

## 🛠️ Recursos Adicionais e Serviços

* **Duplicador Inteligente (`DuplicatorService`)**: Sistema capaz de clonar com precisão Páginas, Seções e Blocos (incluindo replicação de relacionamentos de galeria, depoimentos e marcas parceiras).
* **Drag-and-Drop (`SortableJS`)**: Reordenação intuitiva de banners, páginas, seções e blocos diretamente no painel administrativo, acionando endpoints assíncronos (`fetch` POST) para persistência imediata no banco de dados.
* **Geração de Pix (`PixService`)**: Conversor embutido para gerar códigos Pix "Copia e Cola" e QR Code em formato SVG nativo (injetável inline nas views sem dependência de drivers gráficos como a extensão `gd` do PHP).
* **Maker de CRUD (`CrudPolisher`)**: Serviço voltado à aceleração do desenvolvimento que gera páginas de listagem e formulários administrativos padronizados a partir dos metadados de uma Entidade do Doctrine, registrando-a de forma automatizada no menu lateral da base.

---

## 📋 Guia de Instalação e Execução

### Pré-requisitos
* PHP 8.3 ou superior (com extensões `iconv`, `mbstring`, `pdo_mysql`)
* Composer instalado
* Banco de dados MySQL ou MariaDB

### Passo a Passo

1. **Clonar o Repositório e Instalar as Dependências:**
   ```bash
   composer install
   ```

2. **Configurar as Variáveis de Ambiente:**
   Duplique o arquivo `.env` para `.env.local` e configure a sua conexão com o banco de dados na variável `DATABASE_URL`:
   ```env
   # .env.local
   DATABASE_URL="mysql://usuario:senha@127.0.0.1:3306/wab_sites?serverVersion=8.0.32&charset=utf8mb4"
   ```

3. **Executar as Migrações do Banco:**
   Crie as tabelas e relacionamentos necessários a partir do histórico de migrations do projeto:
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Carregar os Dados Iniciais (Fixtures):**
   Popule o banco com tenants e usuários de desenvolvimento padrão:
   ```bash
   php bin/console doctrine:fixtures:load --no-interaction
   ```
   > [!IMPORTANT]
   > O comando de fixtures criará dois Tenants de desenvolvimento apontando para os hosts locais:
   > * **Tenant A (Domínio: `127.0.0.1`)**: Configurado com o tema `nepe` e cores institucionais (Azul e Amarelo).
   > * **Tenant B (Domínio: `localhost`)**: Configurado com o tema `moderno` e cores escuras (Verde e Cinza).

5. **Iniciar o Servidor Local:**
   Execute a ferramenta de desenvolvimento do Symfony ou o servidor embutido do PHP:
   ```bash
   symfony server:start
   # ou alternativamente:
   php -S 127.0.0.1:8000 -t public
   ```

### Acessos de Teste

| Painel | URL | Usuário | Senha | Nível |
|---|---|---|---|---|
| **SuperAdmin** | `/superadmin` | `superadmin` | `superadmin123` | Global (Gerencia Tenants) |
| **Admin local (Tenant A)** | `http://127.0.0.1:8000/admin` | `admin` | `admin123` | Administrador do Site |
| **Editor (Tenant A)** | `http://127.0.0.1:8000/admin` | `editor` | `editor123` | Escritor de Conteúdo |
| **Revisor (Tenant A)** | `http://127.0.0.1:8000/admin` | `revisor` | `revisor123` | Moderação |

---

## 🏗️ Estrutura do Projeto

```
├── config/                  # Arquivos de configuração do Symfony (Doctrine, Security, etc.)
├── docs/                    # Histórico e documentações internas da evolução do projeto
├── migrations/              # Arquivos de migração estrutural do banco de dados (Doctrine Migrations)
├── public/                  # Diretório raiz público (index.php, CSS, JS, uploads do VichUploader)
├── src/
│   ├── Contract/            # Contratos e Interfaces (ex: TenantAwareInterface)
│   ├── Controller/
│   │   ├── admin/           # Controllers de Gestão de Conteúdo e Configurações (painel administrativo)
│   │   ├── pub/             # Controllers de exibição pública de páginas e categorias resolvidas
│   │   └── superadmin/      # Controller global de gestão de Tenants e impersonificação
│   ├── DataFixtures/        # Dados mockados e estruturados para desenvolvimento rápido (AppFixtures)
│   ├── Doctrine/            # Filtros do banco de dados (TenantFilter)
│   ├── Entity/              # Modelos de dados e Entidades do ORM (Tenant, Page, User, etc.)
│   ├── EventSubscriber/     # Ouvintes de ciclo de vida (TenantSubscriber resolutor de host)
│   ├── Repository/          # Acesso e queries específicas do banco de dados
│   ├── Service/             # Regras de negócio desacopladas (DuplicatorService, PixService, CrudPolisher)
│   └── Twig/                # Extensões e utilitários Twig (TenantExtension e injeção de CSS vars)
├── templates/
│   ├── admin/               # Telas do painel administrativo (estendendo wab_base.html.twig)
│   ├── email/               # Templates HTML de envio de emails de notificação
│   ├── superadmin/          # Telas de controle global
│   └── themes/              # Arquivos Twig dos temas de renderização pública (moderno / nepe)
└── composer.json            # Manifest do Composer contendo as dependências e scripts do projeto
```
