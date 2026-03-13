# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sales and inventory management application with Peruvian tax compliance (SUNAT electronic invoicing) and MercadoLibre marketplace integration. Spanish-localized for Latin American market.

## Tech Stack

- **Backend:** Laravel 11 (PHP 8.2+), Livewire 3.6
- **Frontend:** Blade templates, TailwindCSS 3.3.5, Vite 4.0
- **Database:** MySQL (`shipersales`), Eloquent ORM
- **Auth:** Session-based with Spatie Laravel Permission (RBAC)
- **PDF:** DomPDF + wkhtmltopdf
- **Integrations:** SUNAT (electronic invoicing), MercadoLibre (marketplace), MIGO API (DNI/RUC validation)

## Common Commands

```bash
# Development
npm run dev              # Vite dev server (asset compilation)
npm run build            # Production build
php artisan serve        # Laravel dev server

# Database
php artisan migrate      # Run migrations
php artisan db:seed      # Seed database

# Cache management
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Livewire
php artisan make:livewire ComponentName   # Create new Livewire component

# Testing
php artisan test                          # Run all tests
php artisan test --filter=TestName        # Run specific test
```

## Architecture

### Request Flow

Routes (`routes/web.php`) use permission middleware (`can:resource_name`) → Controllers (`app/Http/Controllers/`) → Blade views with embedded Livewire components.

Most UI logic lives in **Livewire components** (`app/Livewire/`), not in controllers. Livewire handles form state, validation, and reactive updates server-side. Controllers primarily return views.

### Key Directories

- `app/Livewire/` — Feature-organized reactive components (Clients, Sales, Purchases, Articles, etc.). This is where most business logic lives.
- `app/Services/` — External integrations: `SunatService`, `MercadoLibreService`, `MigoApiService`, `PendingDocumentsService`, `UserRedirectService`
- `app/Models/` — 24 Eloquent models. Core domain: Sale/SaleDetail, Purchase/PurchaseDetail, Article, Client, Document
- `app/Utils/Helpers.php` — Autoloaded helper functions
- `resources/views/themes/echo.blade.php` — Main layout template (active theme)
- `resources/js/` — 60+ JS modules organized by theme, vendor, and component

### Domain Model Relationships

- **Sale** → belongs to Client, User, Contact, PaymentMethod; has many SaleDetails and Documents
- **Purchase** → belongs to User, Provider; has many PurchaseDetails
- **SaleDetail/PurchaseDetail** → belongs to Article
- **Document** → electronic invoicing records (SUNAT)
- **Article** → can have ArticleMarketplace (MercadoLibre sync) and ArticleContactPrice

### Authorization

Spatie Permission gates on routes: `can:sales`, `can:purchases`, `can:articles`, `can:store`, `can:users`, `can:documents`, `can:reports`, `can:commissions`, `can:kardex`, etc. Custom middleware `RedirectIfNoDashboardPermission` handles dashboard access.

### Theming

17 color themes defined in `tailwind.config.js` (theme-1 through theme-17) with dark mode support. Active theme: `echo`.

### External Service Integration

- **SUNAT:** Electronic invoicing via Greenter library. Certificate-based auth. Cron endpoint at `/cron/sunat/resend-pending`.
- **MercadoLibre:** OAuth token refresh, access token caching, webhook handler at `POST /api/webhook/handler`.
- **MIGO:** DNI/RUC lookup for Peruvian tax ID validation.

## Configuration

- Locale: `es` (Spanish)
- Timezone: `America/Lima`
- Session/Cache/Queue drivers: `database`
- Environment variables in `.env` for SUNAT credentials, MercadoLibre OAuth, MIGO API token
