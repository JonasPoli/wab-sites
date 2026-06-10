# Estudo: Multi-idioma nos Sites dos Tenants (i18n / l10n)

> **Status:** Estudo — nenhuma linha de código foi alterada.  
> **Escopo:** Sites públicos dos tenants (`pub/`). O painel admin permanece mono-idioma (pt-BR).

---

## 1. Contexto da Arquitetura Atual

### Como o sistema funciona hoje

```
HTTP Request ──► TenantSubscriber (priority 100)
                  │ resolve Tenant pelo Host
                  │ injeta TenantContext
                  │ ativa Doctrine TenantFilter
                  ▼
              Controller (WabPublicController)
                  │ chama theme(template) → "themes/{theme}/{template}"
                  ▼
              Twig Template
                  │ usa {{ currentTenant }}, tenant_css_vars()
                  │ todo texto estático é pt-BR hardcoded
                  ▼
              Response
```

### Entidades com conteúdo textual traduzível

| Entidade | Campos textuais | Notas |
|---|---|---|
| `Tenant` | `name`, `aboutText`, `aboutFullText`, `address`, `seoTitle`, `seoDescription`, `seoKeywords` | Dados gerais do site |
| `Page` | `title`, `slug`, `seoTitle`, `seoDescription` | Slug precisa ser único por locale |
| `PageSection` | `titlePart1`, `titlePart2` | Títulos de seção |
| `PageBlock` | `preTitle`, `title`, `text` | Blocos de conteúdo |
| `PageBlockTeamMember` | `name`, `role`, `bio` | Membros do time |
| `Category` | `title`, `slug`, `description` | Categorias/subcategorias |
| `HeroBanner` | `title`, `subtitle`, `ctaText`, `ctaUrl` | Banners da home |

### Textos estáticos de UI (hardcoded nos templates)

Exemplos encontrados nos templates:
- "Enviar Mensagem", "Nome", "E-mail", "Mensagem" (formulário de contato)
- "Leia mais", "Voltar", "Ver todos" (navegação)
- Textos dos footers, headers, placeholders
- Flash messages nos controllers: "Inscrição realizada com sucesso!", "E-mail inválido ou já inscrito."
- Rotas hardcoded: `/contato`, `/noticias`, `/videos`, `/estudos`, `/categoria`

---

## 2. Estratégias de URL — Comparação

Esta é a decisão arquitetural mais importante, pois impacta SEO, roteamento e UX.

### Opção A — Prefixo de locale na URL (Recomendada)

```
exemplo.com.br/          → redireciona para /pt-br/
exemplo.com.br/pt-br/    → Home em português
exemplo.com.br/en/       → Home em inglês
exemplo.com.br/es/       → Home em espanhol
exemplo.com.br/pt-br/p/sobre-nos
exemplo.com.br/en/p/about-us
```

**Prós:**
- Padrão de mercado consolidado (Google recomenda)
- SEO excelente — cada locale é URL canônica distinta
- Fácil para indexadores rastrearem
- Simples de implementar no Symfony via `LocaleListener`
- Permite `hreflang` correto

**Contras:**
- Requer migração das rotas existentes (quebra backward compatibility)
- URLs ficam ligeiramente mais longas

### Opção B — Subdomínio por locale

```
exemplo.com.br/          → pt-BR
en.exemplo.com.br/       → inglês
es.exemplo.com.br/       → espanhol
```

**Prós:**
- URLs limpas por idioma
- SEO excelente

**Contras:**
- Requer configuração de DNS por tenant (inviável nesta plataforma multitenant)
- TenantSubscriber atualmente usa host para resolver tenant — conflito direto

### Opção C — Parâmetro na query string (Não recomendada)

```
exemplo.com.br/?lang=en
```

**Prós:** Fácil de implementar

**Contras:**
- Google oficialmente desencoraja
- Péssimo para SEO
- Não permite URLs canônicas distintas

### Opção D — Detecção automática sem prefixo (Não recomendada)

```
exemplo.com.br/          → idioma detectado por Accept-Language
```

**Contras:**
- Não indexável por idioma (todos apontam para a mesma URL)
- Conteúdo inconsistente entre usuários — problema de cache
- Impossível compartilhar link num idioma específico

---

## 3. Camadas de Tradução — O que precisa ser traduzido

### Camada 1 — UI/Labels (Symfony Translation Component)

Strings estáticas dos templates Twig. Já existe infra no Symfony (`symfony/translation`), mas o `translation.yaml` atual usa fallback para `en` e o diretório `/translations` está vazio.

**Implementação:** arquivos `.yaml` por locale em `/translations/`

```yaml
# translations/messages.pt_BR.yaml
form.contact.name: "Nome"
form.contact.email: "E-mail"
form.contact.message: "Mensagem"
form.contact.submit: "Enviar Mensagem"
nav.read_more: "Leia mais"
nav.back: "Voltar"
flash.newsletter.success: "Inscrição realizada com sucesso!"

# translations/messages.en.yaml
form.contact.name: "Name"
form.contact.email: "E-mail"
form.contact.message: "Message"
form.contact.submit: "Send Message"
nav.read_more: "Read more"
nav.back: "Back"
flash.newsletter.success: "Successfully subscribed!"
```

**Nos templates Twig:**
```twig
{# Antes #}
<label>Nome</label>

{# Depois #}
<label>{{ 'form.contact.name'|trans }}</label>
```

**Nos controllers PHP:**
```php
// Antes
$this->addFlash('success', 'Inscrição realizada com sucesso!');

// Depois (injetar TranslatorInterface)
$this->addFlash('success', $this->translator->trans('flash.newsletter.success'));
```

### Camada 2 — Conteúdo dos Tenants (Banco de Dados)

Esta é a camada mais complexa. Há **três abordagens** possíveis:

#### 2a. Colunas por locale (JSON na coluna)

```php
// Tenant.php
#[ORM\Column(type: 'json', nullable: true)]
private ?array $aboutTextI18n = null;
// Exemplo: {"pt_BR": "Sobre nós...", "en": "About us..."}
```

**Prós:** Simples, sem novas tabelas  
**Contras:** Queries complexas, não indexável pelo banco, sem tipagem forte, difícil de escalar para muitos campos

#### 2b. Tabela de traduções por entidade (Padrão `*Translation`)

Cria entidades-satélite:

```php
// PageTranslation.php (nova entidade)
class PageTranslation {
    private Page $page;
    private string $locale;    // "pt_BR", "en", "es"
    private string $title;
    private string $slug;
    private ?string $seoTitle;
    private ?string $seoDescription;
}
```

Cada entidade traduzível ganha uma entidade-satélite. Este é o padrão usado por:
- **KnpLabs/DoctrineBehaviors** (trait Translatable)
- **Gedmo/DoctrineExtensions** (Translatable behavior)

**Prós:** Clean, SEO-friendly, consultas eficientes, padrão de mercado  
**Contras:** Muitas novas entidades e migrations, mais complexidade no admin  

#### 2c. Duplicação de Pages por locale

Cada idioma tem suas próprias `Page`s com uma coluna `locale`:

```php
// Page.php
#[ORM\Column(length: 10, nullable: true, options: ['default' => 'pt_BR'])]
private string $locale = 'pt_BR';

// Exemplo: página "Sobre Nós" em pt_BR + "About Us" em en
```

**Prós:** Máxima flexibilidade, admin existente funciona sem grandes mudanças  
**Contras:** Duplicação, difícil manter sincronismo entre versões de idiomas

### Camada 3 — Slugs e Rotas

Os slugs atuais são mono-idioma e são usados em rotas como `/p/{slug}`. Com i18n:

```
/pt-br/p/sobre-nos    → Page{locale: pt_BR, slug: sobre-nos}
/en/p/about-us        → Page{locale: en, slug: about-us}
```

O `PageRepository::findOneBy(['slug' => $slug])` precisaria filtrar também por locale.

### Camada 4 — SEO (hreflang)

Para cada página multilíngue, o `<head>` deve incluir:

```html
<link rel="alternate" hreflang="pt-BR" href="https://exemplo.com/pt-br/p/sobre-nos">
<link rel="alternate" hreflang="en"    href="https://exemplo.com/en/p/about-us">
<link rel="alternate" hreflang="x-default" href="https://exemplo.com/pt-br/p/sobre-nos">
```

---

## 4. Impacto por Camada do Sistema

### 4.1 TenantSubscriber (EventSubscriber)

**Mudança necessária:** após resolver o tenant, detectar o locale e setá-lo no `Request`:

```php
// Antes — só resolve tenant
$this->tenantContext->setTenant($tenant);

// Depois — resolve tenant + locale
$this->tenantContext->setTenant($tenant);
$locale = $this->detectLocale($request, $tenant);
$request->setLocale($locale);
$request->getSession()->set('_locale', $locale);
```

Lógica de `detectLocale()`:
1. Verifica segmento da URL: `/en/`, `/pt-br/`
2. Se não encontrado, verifica cookie `_locale`
3. Se não encontrado, verifica `Accept-Language` header
4. Fallback: locale padrão do tenant (`Tenant::$defaultLocale`)

### 4.2 TenantContext (Service)

Deve armazenar também o locale ativo:

```php
class TenantContext {
    private ?Tenant $tenant = null;
    private string $locale = 'pt_BR';    // ← novo

    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): void { ... }
}
```

### 4.3 Tenant Entity

Campos novos necessários:
```php
// Idiomas suportados por este tenant
#[ORM\Column(type: 'json', options: ['default' => '["pt_BR"]'])]
private array $supportedLocales = ['pt_BR'];

// Idioma padrão do tenant
#[ORM\Column(length: 10, options: ['default' => 'pt_BR'])]
private string $defaultLocale = 'pt_BR';
```

### 4.4 WabPublicController — Rotas

**Antes:**
```php
#[Route('/', name: 'pub_home')]
#[Route('/p/{slug}', name: 'pub_page_show')]
#[Route('/categoria/{slug}', name: 'pub_category')]
```

**Depois (Opção A — prefixo locale):**
```php
#[Route('/{_locale}/', name: 'pub_home', requirements: ['_locale' => 'pt-br|en|es'])]
#[Route('/{_locale}/p/{slug}', name: 'pub_page_show')]
#[Route('/{_locale}/categoria/{slug}', name: 'pub_category')]
```

O Symfony reconhece `{_locale}` como parâmetro especial e seta automaticamente o locale do request.

**Redirect da raiz:**
```php
#[Route('/', name: 'pub_root')]
public function root(): Response {
    $locale = $this->tenantContext->getLocale();
    return $this->redirectToRoute('pub_home', ['_locale' => $locale]);
}
```

### 4.5 Templates Twig

**Mudanças nos base.html.twig de cada tema:**
```twig
{# Antes #}
<html lang="pt-BR">

{# Depois #}
<html lang="{{ app.request.locale|replace('_', '-') }}">
```

**Seletor de idioma no header:**
```twig
<div class="lang-switcher">
  {% for locale in currentTenant.supportedLocales %}
    <a href="{{ path('pub_home', {_locale: locale}) }}"
       class="{{ app.request.locale == locale ? 'active' : '' }}">
      {{ locale|upper }}
    </a>
  {% endfor %}
</div>
```

**Textos estáticos:**
```twig
{# Antes #}
<button>Enviar</button>

{# Depois #}
<button>{{ 'btn.send'|trans }}</button>
```

### 4.6 TenantExtension (Twig Global)

Deve expor o locale ativo como global:
```php
public function getGlobals(): array {
    return [
        'currentTenant' => ...,
        'currentLocale' => $this->tenantContext->getLocale(),    // ← novo
        'availableLocales' => $this->tenantContext->getTenant()?->getSupportedLocales() ?? ['pt_BR'],  // ← novo
        ...
    ];
}
```

---

## 5. Configuração do Tenant — Novas Opções Admin

O painel admin precisaria de novas configurações por tenant:

```
Tenant → Configurações → Idiomas
┌─────────────────────────────────────────┐
│ Idioma padrão: [Português (pt-BR) ▼]   │
│                                         │
│ Idiomas ativos:                         │
│ ☑ Português (pt-BR)                    │
│ ☐ Inglês (en)                          │
│ ☐ Espanhol (es)                        │
│ ☐ Francês (fr)                         │
└─────────────────────────────────────────┘
```

---

## 6. Abordagem Recomendada — Implementação Faseada

### Fase 1 — Infra base (sem quebrar nada)

- Adicionar `supportedLocales` e `defaultLocale` ao `Tenant`
- Criar migration
- Atualizar `TenantSubscriber` para detectar locale
- Atualizar `TenantContext` para guardar locale
- Configurar `translation.yaml` com pt_BR como default
- Criar `/translations/messages.pt_BR.yaml` com todas as strings

### Fase 2 — Rotas e navegação

- Adicionar rotas com `{_locale}` prefixo
- Manter rotas antigas com redirect (backward compat)
- Adicionar seletor de idioma nos headers
- Adicionar `hreflang` nos `<head>` das bases

### Fase 3 — Tradução de UI (templates)

- Substituir strings hardcoded nos templates por `|trans`
- Substituir strings nos controllers por `$translator->trans()`
- Criar arquivos de tradução para cada locale suportado

### Fase 4 — Conteúdo do banco (escolher abordagem)

**Recomendação:** Abordagem 2c (Page com `locale` column) para manter simplicidade:
- Adicionar `locale` column às entidades: `Page`, `PageBlock`, `PageSection`, `PageBlockTeamMember`
- Admin permite criar "versão EN" de uma página existente
- Repositórios filtram por locale ativo
- Migração das páginas existentes recebem `locale = 'pt_BR'` automaticamente

---

## 7. Riscos e Considerações

| Risco | Impacto | Mitigação |
|---|---|---|
| Quebra de URLs existentes | Alto — SEO penaliza 301 tardio | Redirects permanentes 301 das URLs antigas para novas com locale |
| Slugs duplicados entre locales | Médio | Unique index em `(slug, locale, tenant_id)` |
| Cache de páginas por locale | Médio | Incluir locale na chave de cache |
| Volume de trabalho de tradução | Alto | Integração futura com API de tradução automática (DeepL/Google) |
| Consistência de conteúdo | Médio | Interface admin que mostra páginas "sem tradução" |
| Tenant mono-idioma | Baixo | Se `supportedLocales` tem só 1 idioma, ocultar seletor de idioma |

---

## 8. Dependências Symfony Relevantes

| Pacote | Já instalado? | Uso |
|---|---|---|
| `symfony/translation` | ✅ Sim (vejo no translation.yaml) | Tradução de UI |
| `symfony/locale` | ✅ Parte do framework | Formatação por locale |
| `knplabs/doctrine-behaviors` | ❌ Não | Padrão Translatable automático (Fase 4 alternativa) |
| `stof/doctrine-extensions-bundle` | ❌ Não | Alternativa ao KnpLabs |

> **Nota:** Para a Fase 4, é possível implementar sem pacotes externos usando a abordagem de coluna `locale` + entidades separadas manualmente.

---

## 9. Exemplo de Fluxo Completo (Opção A, Fase Final)

```
Usuário acessa: https://escritorio.adv.br/en/p/practice-areas
                                             ▲  ▲
                                          locale slug em inglês

TenantSubscriber:
  1. host → tenant = Escritório XYZ
  2. segmento /en → locale = 'en'
  3. request.setLocale('en')
  4. TenantContext.setLocale('en')

WabPublicController::pageShow():
  1. slug = 'practice-areas', locale = 'en'
  2. PageRepository::findBySlugAndLocale('practice-areas', 'en', $tenant)
  3. render('themes/wab/page.html.twig', ['page' => $page])

Twig page.html.twig:
  - <html lang="en">
  - {{ 'nav.read_more'|trans }} → "Read more"
  - {{ page.title }} → "Practice Areas"
  - hreflang pt-BR → /pt-br/p/areas-de-atuacao
  - hreflang en    → /en/p/practice-areas
```

---

## 10. Estimativa de Esforço

| Fase | Esforço estimado | Complexidade |
|---|---|---|
| Fase 1 — Infra base | 1-2 dias | Baixa |
| Fase 2 — Rotas e navegação | 1 dia | Média |
| Fase 3 — Tradução de UI | 2-3 dias | Média (volume de strings) |
| Fase 4 — Conteúdo do banco | 3-5 dias | Alta |
| **Total** | **7-11 dias** | **Alta** |

> Fase 4 pode ser entregue iterativamente: primeiro `Page` e `PageBlock`, depois entidades menores.
