# Guia Arquitetural e de Implementação: Sistema de Importação e Exportação (Multi-tenant)

Este documento descreve detalhadamente a arquitetura, o fluxo de dados, os requisitos de banco de dados, a estrutura de arquivos e o design da interface do usuário para implementar um sistema robusto de **Exportação e Importação de Tenants** para a plataforma multi-tenant.

O objetivo principal é permitir a portabilidade total de um Tenant (Site) completo — incluindo todas as páginas, seções, blocos, configurações visuais, banco de dados e arquivos de mídia associados — de uma instância do sistema para outra em servidores distintos.

---

## 1. Visão Geral do Fluxo

```mermaid
flowchart TD
    subgraph Servidor Origem (Exportação)
        A[Selecionar Tenant] --> B[Consultar Banco de Dados]
        B --> C[Serializar Metadados para JSON]
        A --> D[Coletar Arquivos Físicos em public/uploads]
        C --> E[Empacotar tudo em arquivo .ZIP]
        D --> E
        E --> F[Download do ZIP]
    end
    subgraph Servidor Destino (Importação)
        F --> G[Upload do ZIP]
        G --> H[Extração em Diretório Temporário]
        H --> I[Análise de Metadados e Conflitos]
        I --> J{Existem Duplicidades?}
        J -- Sim --> K[Wizard de Resolução no Painel SuperAdmin]
        K --> L[Aplicar Substituições do Admin]
        J -- Não --> M[Iniciar Transação de Banco de Dados]
        L --> M
        M --> N[Inserir Entidades com Re-mapeamento de IDs]
        N --> O[Mover Arquivos Físicos para Mapeamentos VichUploader]
        O --> P[Confirmar Transação - Commit]
    end
```

---

## 2. Estrutura do Arquivo de Exportação (.ZIP)

O arquivo gerado para exportação deve ser um arquivo ZIP autocontido com o seguinte layout interno:

```text
tenant_export_[domain]_[timestamp].zip
├── metadata.json
└── media/
    ├── tenant_logo/
    │   └── logo-principal.png
    ├── tenant_dark_logo/
    │   └── logo-escuro.png
    ├── tenant_favicon/
    │   └── favicon.ico
    ├── tenant_about/
    │   └── sobre-nos.jpg
    ├── page_cover/
    │   └── capa-pagina-1.png
    ├── partner_logo/
    │   └── logo-parceiro.png
    ├── testimonial_avatar/
    │   └── avatar-depoente.jpg
    ├── team_member_image/
    │   └── foto-membro-equipe.jpg
    ├── page_block/
    │   └── imagem-bloco.jpg
    └── page_block_gallery/
        └── foto-galeria-1.jpg
```

### O arquivo `metadata.json`
Contém as configurações de sistema, dados relacionais serializados e hashes de arquivos para garantir a integridade dos dados. Estrutura recomendada:

```json
{
  "system": {
    "platform_version": "2026.1",
    "export_timestamp": "2026-05-21T10:45:00-03:00",
    "schema_hash": "e5192c7324fa..."
  },
  "tenant": {
    "old_id": 14,
    "name": "Nome do Inquilino",
    "domain": "dominio-atual.local",
    "theme": "cetec",
    "primaryColor": "#0044cc",
    "secondaryColor": "#ffaa00",
    "primaryColorDark": "#3b82f6",
    "secondaryColorDark": "#fbbf24",
    "contactEmail": "contato@dominio.com.br",
    "address": "Rua Exemplo, 123",
    "phone": "(16) 99999-9999",
    "logo": "logo-principal.png",
    "darkLogo": "logo-escuro.png",
    "favicon": "favicon.ico",
    "aboutImage": "sobre-nos.jpg",
    "aboutText": "Resumo institucional...",
    "aboutFullText": "Texto completo institucional...",
    "landingPageMode": true,
    "fontSettings": {},
    "navigationSettings": {}
  },
  "users": [
    {
      "username": "admin_cetec",
      "name": "Administrador Principal",
      "email": "admin@dominio.com",
      "workGroup": 0,
      "roles": ["ROLE_ADMIN"],
      "password": "$bcrypt_hash_senha_original..."
    }
  ],
  "categories": [
    {
      "old_id": 101,
      "name": "Cursos Técnicos",
      "slug": "cursos-tecnicos",
      "icon": "bi bi-journal",
      "preTitle": "Formação Profissional",
      "description": "...",
      "parent_old_id": null
    }
  ],
  "pages": [
    {
      "old_id": 201,
      "title": "Técnico em Enfermagem",
      "slug": "tecnico-em-enfermagem",
      "showInHeader": true,
      "showInFooter": false,
      "seoTitle": "...",
      "seoDescription": "...",
      "coverImage": "capa-pagina-1.png",
      "position": 1,
      "category_old_id": 101,
      "showTitle": true,
      "sections": [
        {
          "old_id": 301,
          "position": 0,
          "title": "Apresentação do Curso",
          "showTitle": true,
          "active": true,
          "cssClass": "bg-slate-50",
          "blocks": [
            {
              "old_id": 401,
              "type": "text_image",
              "position": 0,
              "title": "Diferenciais Técnicos",
              "preTitle": "O que você aprenderá",
              "text": "<p>Conteúdo em HTML...</p>",
              "config": {
                "ctaText": "Inscreva-se Já",
                "ctaLink": "#contato"
              },
              "teamMembers": [],
              "testimonials": [],
              "galleryImages": [],
              "partnerLogos": []
            }
          ]
        }
      ]
    }
  ]
}
```

---

## 3. Motor de Exportação (`TenantExporter`)

A classe de exportação deve carregar todos os dados de forma indexada e empacotar os arquivos binários correspondentes do sistema de arquivos.

```php
namespace App\Service;

use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\Page;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class TenantExporter
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $projectDir
    ) {}

    public function export(Tenant $tenant, string $outputPath): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $data = [
            'system' => [
                'platform_version' => '2026.1',
                'export_timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            'tenant' => $this->serializeTenant($tenant),
            'users' => $this->serializeUsers($tenant),
            'categories' => $this->serializeCategories($tenant),
            'pages' => $this->serializePages($tenant),
        ];

        // 1. Inserir arquivo de metadados
        $zip->addFromString('metadata.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 2. Localizar e anexar mídia física
        $this->collectMediaFiles($tenant, $data, $zip);

        $zip->close();
        return true;
    }

    private function serializeTenant(Tenant $t): array
    {
        return [
            'old_id' => $t->getId(),
            'name' => $t->getName(),
            'domain' => $t->getDomain(),
            'theme' => $t->getTheme(),
            'primaryColor' => $t->getPrimaryColor(),
            'secondaryColor' => $t->getSecondaryColor(),
            'primaryColorDark' => $t->getPrimaryColorDark(),
            'secondaryColorDark' => $t->getSecondaryColorDark(),
            'contactEmail' => $t->getContactEmail(),
            'address' => $t->getAddress(),
            'phone' => $t->getPhone(),
            'mapsEmbedUrl' => $t->getMapsEmbedUrl(),
            'logo' => $t->getLogo(),
            'darkLogo' => $t->getDarkLogo(),
            'favicon' => $t->getFavicon(),
            'aboutImage' => $t->getAboutImage(),
            'aboutText' => $t->getAboutText(),
            'aboutFullText' => $t->getAboutFullText(),
            'landingPageMode' => $t->isLandingPageMode(),
            'fontSettings' => $t->getFontSettings(),
            'navigationSettings' => $t->getNavigationSettings(),
        ];
    }

    private function serializeUsers(Tenant $t): array
    {
        $users = $this->em->getRepository(User::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($users as $u) {
            $serialized[] = [
                'username' => $u->getUsername(),
                'name' => $u->getName(),
                'email' => $u->getEmail(),
                'workGroup' => $u->getWorkGroup(),
                'roles' => $u->getRoles(),
                'password' => $u->getPassword(), // Preserva a hash da senha
            ];
        }
        return $serialized;
    }

    private function serializeCategories(Tenant $t): array
    {
        $cats = $this->em->getRepository(Category::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($cats as $c) {
            $serialized[] = [
                'old_id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'icon' => $c->getIcon(),
                'preTitle' => $c->getPreTitle(),
                'description' => $c->getDescription(),
                'parent_old_id' => $c->getParent()?->getId(),
            ];
        }
        return $serialized;
    }

    private function serializePages(Tenant $t): array
    {
        $pages = $this->em->getRepository(Page::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($pages as $p) {
            $sections = [];
            foreach ($p->getSections() as $s) {
                $blocks = [];
                foreach ($s->getBlocks() as $b) {
                    $blocks[] = [
                        'old_id' => $b->getId(),
                        'type' => $b->getType(),
                        'position' => $b->getPosition(),
                        'title' => $b->getTitle(),
                        'preTitle' => $b->getPreTitle(),
                        'text' => $b->getText(),
                        'config' => $b->getConfig(),
                        'teamMembers' => $this->serializeTeamMembers($b),
                        'testimonials' => $this->serializeTestimonials($b),
                        'galleryImages' => $this->serializeGalleryImages($b),
                        'partnerLogos' => $this->serializePartnerLogos($b),
                    ];
                }
                $sections[] = [
                    'old_id' => $s->getId(),
                    'position' => $s->getPosition(),
                    'title' => $s->getTitle(),
                    'showTitle' => $s->isShowTitle(),
                    'active' => $s->isActive(),
                    'cssClass' => $s->getCssClass(),
                    'bgImage' => $s->getBgImage(),
                    'bgVideo' => $s->getBgVideo(),
                    'blocks' => $blocks
                ];
            }

            $serialized[] = [
                'old_id' => $p->getId(),
                'title' => $p->getTitle(),
                'slug' => $p->getSlug(),
                'showInHeader' => $p->isShowInHeader(),
                'showInFooter' => $p->isShowInFooter(),
                'seoTitle' => $p->getSeoTitle(),
                'seoDescription' => $p->getSeoDescription(),
                'coverImage' => $p->getCoverImage(),
                'position' => $p->getPosition(),
                'category_old_id' => $p->getCategory()?->getId(),
                'showTitle' => $p->isShowTitle(),
                'sections' => $sections,
            ];
        }
        return $serialized;
    }

    // Métodos privados para serializar relações internas dos blocos de páginas...

    private function collectMediaFiles(Tenant $tenant, array $data, ZipArchive $zip): void
    {
        // 1. Arquivos globais do Tenant
        $this->addMedia($zip, 'tenant_logo', $tenant->getLogo());
        $this->addMedia($zip, 'tenant_dark_logo', $tenant->getDarkLogo());
        $this->addMedia($zip, 'tenant_favicon', $tenant->getFavicon());
        $this->addMedia($zip, 'tenant_about', $tenant->getAboutImage());

        // 2. Capas de páginas
        foreach ($data['pages'] as $p) {
            $this->addMedia($zip, 'page_cover', $p['coverImage']);
            foreach ($p['sections'] as $s) {
                $this->addMedia($zip, 'section/bg', $s['bgImage']);
                $this->addMedia($zip, 'section/video', $s['bgVideo']);
                foreach ($s['blocks'] as $b) {
                    // Arquivos específicos de relações do bloco
                    foreach ($b['teamMembers'] as $m) {
                        $this->addMedia($zip, 'team_member_image', $m['image']);
                    }
                    foreach ($b['testimonials'] as $t) {
                        $this->addMedia($zip, 'testimonial_avatar', $t['avatar']);
                    }
                    foreach ($b['galleryImages'] as $g) {
                        $this->addMedia($zip, 'page_block_gallery', $g['image']);
                    }
                    foreach ($b['partnerLogos'] as $pl) {
                        $this->addMedia($zip, 'partner_logo', $pl['logo']);
                    }
                }
            }
        }
    }

    private function addMedia(ZipArchive $zip, string $mapping, ?string $filename): void
    {
        if (!$filename) return;
        $sourcePath = sprintf('%s/public/uploads/%s/%s', $this->projectDir, $mapping, $filename);
        if (file_exists($sourcePath)) {
            $zip->addFile($sourcePath, sprintf('media/%s/%s', $mapping, $filename));
        }
    }
}
```

---

## 4. Análise de Conflitos e Acessibilidade (Duplicidade no Destino)

Antes de gravar qualquer dado, o motor de importação deve extrair o JSON e executar testes rigorosos contra o banco de dados de destino. A tabela abaixo lista os campos críticos globais e de escopo que necessitam de intervenção ou avaliação:

| Nível de Restrição | Campo de Banco | Entidade | Risco de Colisão | Solução Obrigatória / Resolução |
| :--- | :--- | :--- | :--- | :--- |
| **Crítico Global** | `domain` | `Tenant` | **Extremo** (Único no BD). Um domínio não pode rodar em dois Tenants. | **Mudar Obrigatório**: O SuperAdmin deve preencher um domínio inédito para o novo tenant. |
| **Crítico Global** | `username` | `User` | **Extremo** (Único no BD). Chave do sistema de segurança. | **Mudar Obrigatório**: Renomear o login no destino se houver conflito (ex: de `admin` para `admin_tenant`). |
| **Crítico Global** | `email` | `User` | **Extremo** (Único no BD). Recuperação de senha única. | **Mudar Obrigatório**: Alterar para um e-mail novo ou atribuir o usuário ao tenant existente. |
| **Aviso de Duplicidade** | `name` | `Tenant` | **Alto** (Confusão de Identidade). | **Recomendado**: Modificar o título amigável do site (ex: adicionar sufixo " - Cópia"). |
| **Escopo do Tenant** | `slug` | `Page` / `Category` | **Nulo** (Pois o Tenant ID será novo e os caminhos públicos resolvem em escopos isolados). | **Ignorar / Auto-resolver**: Sem colisões desde que o Tenant tenha um domínio próprio. |

---

## 5. Interface Visual do Wizard de Importação (SuperAdmin)

Se o arquivo importado for carregado e a rotina detectar conflitos de nomes ou domínios, a interface apresentará uma tela de controle rica e interativa de **Resolução de Conflitos**, bloqueando a transação até que todos os conflitos globais sejam mitigados pelo SuperAdmin.

### Protótipo da UI do Wizard de Importação

````carousel
```markdown
# 📥 Importar Novo Site (Tenant)
### Resumo do Pacote Carregado:
* **Tema**: `CETEC` 
* **Páginas**: 14 seções | 32 blocos de conteúdo
* **Arquivos**: 185 MB de imagens e mídia
* **Instância Original**: `cetec-araraquara.local`

> [!WARNING]
> **Identificamos 4 conflitos de unicidade com o servidor local.** 
> Você precisa resolver as pendências abaixo para prosseguir com a importação segura.
```
<!-- slide -->
```markdown
### 🌐 1. Configurações Globais do Site (Tenant)

* **Domínio de Acesso** 🔴 *Conflito detectado: `cetec-araraquara.local` já existe nesta rede.*
  [ dominio-cetec-novo.com.br         ] (Digite o domínio inédito para esta cópia)

* **Nome do Site / Instituição** ⚠️ *Aviso: "CETEC Regional" já está em uso.*
  [ CETEC - Unidade Catanduva         ] (Nome de exibição amigável do Tenant)
```
<!-- slide -->
```markdown
### 👤 2. Contas de Usuários e Segurança

* **Usuário: `admin`** 🔴 *O login `admin` já está associado a outro tenant.*
  [ admin_catanduva                 ] (Altere o username para ser exclusivo no servidor)

* **E-mail: `coordenacao@cetec.com`** 🔴 *Este e-mail já pertence a outro usuário.*
  [ coordenacao.catanduva@cetec.com ] (Altere para um e-mail único de administração)
```
<!-- slide -->
```markdown
> [!TIP]
> **Pronto para Concluir!**
> Uma vez preenchidos os dados de resolução acima, clique no botão para aplicar a transação.
> 
> [ 📥 Executar Importação com Segurança ] (Botão Principal - Ativo quando validações passam)
```
````

---

## 6. Motor de Importação (`TenantImporter`)

O motor de importação deve abrir uma **transação transacional (Doctrine Transaction)**. Caso ocorra qualquer exceção durante a cópia dos arquivos ou inserção de dados, a operação completa sofre um rollback para garantir estabilidade e nenhuma poluição no banco.

### Mecanismo de Mapeamento de IDs Relacionais
Como os registros são criados em lote, o ID (auto-incremento) de cada entidade muda ao ser gravado no banco de destino. Portanto, o importador deve gerenciar uma **tabela de tradução em memória** (Dicionário de Tradução) para atualizar as chaves estrangeiras perfeitamente:

```php
$categoryMap = []; // [ 'old_id' => 'new_id' ]
$pageMap     = []; // [ 'old_id' => 'new_id' ]
```

```php
namespace App\Service;

use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\PageBlock;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class TenantImporter
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $projectDir,
        private string $tmpExtractDir
    ) {}

    public function analyze(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Não foi possível abrir o arquivo ZIP.');
        }

        $jsonContent = $zip->getFromName('metadata.json');
        if (!$jsonContent) {
            throw new \Exception('O arquivo de metadados metadata.json não foi localizado no arquivo exportado.');
        }

        $data = json_decode($jsonContent, true);
        
        // Validação de segurança básica da versão de plataforma
        if (!isset($data['system']['platform_version'])) {
            throw new \Exception('Versão da plataforma inválida ou não declarada.');
        }

        $conflicts = [
            'domain' => false,
            'tenant_name' => false,
            'users' => []
        ];

        // 1. Validar unicidade do domínio do Tenant
        $existingTenant = $this->em->getRepository(Tenant::class)->findOneBy(['domain' => $data['tenant']['domain']]);
        if ($existingTenant) {
            $conflicts['domain'] = $data['tenant']['domain'];
        }

        // 2. Validar nome do Tenant
        $existingName = $this->em->getRepository(Tenant::class)->findOneBy(['name' => $data['tenant']['name']]);
        if ($existingName) {
            $conflicts['tenant_name'] = $data['tenant']['name'];
        }

        // 3. Validar usuários e emails
        foreach ($data['users'] as $u) {
            $existingUsername = $this->em->getRepository(User::class)->findOneBy(['username' => $u['username']]);
            $existingEmail = $u['email'] ? $this->em->getRepository(User::class)->findOneBy(['email' => $u['email']]) : null;

            if ($existingUsername || $existingEmail) {
                $conflicts['users'][] = [
                    'original_username' => $u['username'],
                    'original_email' => $u['email'],
                    'username_collision' => (bool)$existingUsername,
                    'email_collision' => (bool)$existingEmail
                ];
            }
        }

        return [
            'metadata' => $data,
            'conflicts' => $conflicts,
            'has_conflicts' => ($conflicts['domain'] || !empty($conflicts['users']))
        ];
    }

    public function import(array $data, array $resolutions, string $zipPath): Tenant
    {
        $filesystem = new Filesystem();
        $tempWorkdir = $this->tmpExtractDir . '/' . uniqid('import_');

        // Extrair ZIP temporariamente
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tempWorkdir);
            $zip->close();
        }

        $this->em->beginTransaction();
        try {
            // 1. Criar e preencher o Tenant aplicando resoluções
            $tenant = new Tenant();
            $tenant->setName($resolutions['tenant_name'] ?? $data['tenant']['name']);
            $tenant->setDomain($resolutions['domain'] ?? $data['tenant']['domain']);
            $tenant->setTheme($data['tenant']['theme']);
            $tenant->setPrimaryColor($data['tenant']['primaryColor']);
            $tenant->setSecondaryColor($data['tenant']['secondaryColor']);
            $tenant->setPrimaryColorDark($data['tenant']['primaryColorDark']);
            $tenant->setSecondaryColorDark($data['tenant']['secondaryColorDark']);
            $tenant->setContactEmail($data['tenant']['contactEmail']);
            $tenant->setAddress($data['tenant']['address']);
            $tenant->setPhone($data['tenant']['phone']);
            $tenant->setMapsEmbedUrl($data['tenant']['mapsEmbedUrl']);
            $tenant->setLandingPageMode($data['tenant']['landingPageMode']);
            $tenant->setFontSettings($data['tenant']['fontSettings']);
            $tenant->setNavigationSettings($data['tenant']['navigationSettings']);

            // Mapeia imagens associadas ao Tenant
            if (!empty($data['tenant']['logo'])) {
                $tenant->setLogo($data['tenant']['logo']);
                $this->relocateMedia($tempWorkdir, 'tenant_logo', $data['tenant']['logo']);
            }
            if (!empty($data['tenant']['darkLogo'])) {
                $tenant->setDarkLogo($data['tenant']['darkLogo']);
                $this->relocateMedia($tempWorkdir, 'tenant_dark_logo', $data['tenant']['darkLogo']);
            }
            if (!empty($data['tenant']['favicon'])) {
                $tenant->setFavicon($data['tenant']['favicon']);
                $this->relocateMedia($tempWorkdir, 'tenant_favicon', $data['tenant']['favicon']);
            }
            if (!empty($data['tenant']['aboutImage'])) {
                $tenant->setAboutImage($data['tenant']['aboutImage']);
                $this->relocateMedia($tempWorkdir, 'tenant_about', $data['tenant']['aboutImage']);
            }

            $this->em->persist($tenant);

            // 2. Importar Usuários do Tenant resolvendo colisões de nome e email
            foreach ($data['users'] as $uData) {
                $user = new User();
                
                $username = $uData['username'];
                $email = $uData['email'];

                // Aplica resoluções fornecidas pelo SuperAdmin
                if (isset($resolutions['users'][$username])) {
                    $username = $resolutions['users'][$username]['username'] ?? $username;
                    $email = $resolutions['users'][$username]['email'] ?? $email;
                }

                $user->setUsername($username);
                $user->setName($uData['name']);
                $user->setEmail($email);
                $user->setWorkGroup($uData['workGroup']);
                $user->setRoles($uData['roles']);
                $user->setPassword($uData['password']); // Mantém a hash Bcrypt de segurança original
                $user->setTenant($tenant);

                $this->em->persist($user);
            }

            // 3. Importar Categorias com tradução de hierarquia recursiva
            $categoryMap = [];
            $this->importCategories($data['categories'], $tenant, $categoryMap);

            // 4. Importar Páginas, Seções e Blocos
            foreach ($data['pages'] as $pData) {
                $page = new Page();
                $page->setTenant($tenant);
                $page->setTitle($pData['title']);
                $page->setSlug($pData['slug']);
                $page->setShowInHeader($pData['showInHeader']);
                $page->setShowInFooter($pData['showInFooter']);
                $page->setSeoTitle($pData['seoTitle']);
                $page->setSeoDescription($pData['seoDescription']);
                $page->setPosition($pData['position']);
                $page->setShowTitle($pData['showTitle']);

                if ($pData['coverImage']) {
                    $page->setCoverImage($pData['coverImage']);
                    $this->relocateMedia($tempWorkdir, 'page_cover', $pData['coverImage']);
                }

                // Relacionar com nova categoria
                if ($pData['category_old_id'] && isset($categoryMap[$pData['category_old_id']])) {
                    $page->setCategory($categoryMap[$pData['category_old_id']]);
                }

                $this->em->persist($page);

                // Importar Seções Internas da Página
                foreach ($pData['sections'] as $sData) {
                    $section = new PageSection();
                    $section->setPage($page);
                    $section->setPosition($sData['position']);
                    $section->setTitle($sData['title']);
                    $section->setShowTitle($sData['showTitle']);
                    $section->setActive($sData['active']);
                    $section->setCssClass($sData['cssClass']);

                    if ($sData['bgImage']) {
                        $section->setBgImage($sData['bgImage']);
                        $this->relocateMedia($tempWorkdir, 'section/bg', $sData['bgImage']);
                    }
                    if ($sData['bgVideo']) {
                        $section->setBgVideo($sData['bgVideo']);
                        $this->relocateMedia($tempWorkdir, 'section/video', $sData['bgVideo']);
                    }

                    $this->em->persist($section);

                    // Importar Blocos Internos da Seção
                    foreach ($sData['blocks'] as $bData) {
                        $block = new PageBlock();
                        $block->setSection($section);
                        $block->setType($bData['type']);
                        $block->setPosition($bData['position']);
                        $block->setTitle($bData['title']);
                        $block->setPreTitle($bData['preTitle']);
                        $block->setText($bData['text']);
                        $block->setConfig($bData['config']);

                        $this->em->persist($block);

                        // Mapear elementos internos de mídia para o bloco do carrossel/equipe/parceiros...
                        $this->importBlockRelations($bData, $block, $tempWorkdir);
                    }
                }
            }

            // Flush e finalização da transação
            $this->em->flush();
            $this->em->commit();

            // Limpar diretório de trabalho temporário
            $filesystem->remove($tempWorkdir);

            return $tenant;
        } catch (\Exception $e) {
            $this->em->rollback();
            $filesystem->remove($tempWorkdir);
            throw $e;
        }
    }

    private function importCategories(array $categories, Tenant $tenant, array &$categoryMap): void
    {
        // Importa em duas passagens para primeiro persistir os nós pais e depois atribuir o auto-relacionamento hierárquico
        $pendingChildren = [];

        foreach ($categories as $cData) {
            $category = new Category();
            $category->setTenant($tenant);
            $category->setName($cData['name']);
            $category->setSlug($cData['slug']);
            $category->setIcon($cData['icon']);
            $category->setPreTitle($cData['preTitle']);
            $category->setDescription($cData['description']);
            
            $this->em->persist($category);
            $this->em->flush(); // Garante a geração do ID destino

            $categoryMap[$cData['old_id']] = $category;

            if ($cData['parent_old_id']) {
                $pendingChildren[] = [
                    'entity' => $category,
                    'parent_old_id' => $cData['parent_old_id']
                ];
            }
        }

        // Resolvendo auto-relacionamento recursivo
        foreach ($pendingChildren as $child) {
            if (isset($categoryMap[$child['parent_old_id']])) {
                $child['entity']->setParent($categoryMap[$child['parent_old_id']]);
            }
        }
        $this->em->flush();
    }

    private function relocateMedia(string $tempWorkdir, string $mapping, string $filename): void
    {
        $source = sprintf('%s/media/%s/%s', $tempWorkdir, $mapping, $filename);
        $destinationDir = sprintf('%s/public/uploads/%s', $this->projectDir, $mapping);
        $destination = $destinationDir . '/' . $filename;

        if (file_exists($source)) {
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            copy($source, $destination);
        }
    }

    private function importBlockRelations(array $bData, PageBlock $block, string $tempWorkdir): void
    {
        // Implementar as lógicas individuais que lêem arrays internos como 'teamMembers', 
        // instanciam novos PageBlockTeamMember, invocam relocateMedia para mover a foto do membro 
        // e persistem as sub-entidades do Doctrine.
    }
}
```

---

## 7. Requisitos de Segurança e Acessibilidade

Para proteger a integridade do sistema operacional e do banco de dados ao aceitar pacotes gerados externamente, os seguintes requisitos devem ser implementados na fase de produção:

1. **Assinatura Digital / Criptografia**: O pacote de exportação pode incluir um hash HMAC gerado com o `APP_SECRET` do servidor de origem na raiz do ZIP (`signature.sha256`). Se o destino compartilhar a mesma chave de criptografia, a autenticidade é aprovada instantaneamente.
2. **Sanitização de HTML em Blocos**: A propriedade `text` de todos os blocos de páginas deve passar por uma limpeza HTML (HTML Purifier) ao ser importada, mitigando brechas de XSS induzidas através de scripts importados de forma maliciosa.
3. **Limitação de Tamanho de ZIP**: Impor limites via validador do Symfony Form para uploads (`maxSize: "200M"`) para mitigar ataques de negação de serviço (DoS/Zip Bomb).
4. **Preservação e Segurança de Senhas**: As senhas de usuários continuam salvas em suas hashes originais de hashing (Bcrypt). Ao migrar usuários, as credenciais originais de login continuam ativas sem a necessidade de expor ou decodificar a senha original.
