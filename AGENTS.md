# AGENTS.md - Pressbooks Memorious

## Project Overview

Pressbooks Memorious is a standalone WordPress plugin that adds fast, faceted search to Pressbooks multisite networks. It indexes full chapter text, book metadata, and contributors into Typesense and provides an admin bar search UI. Requires **PHP 8.3+** and **WordPress 6.8+ Multisite** with Pressbooks active.

## Build & Test Commands

### PHP Tests (PHPUnit)

Tests require a WordPress Multisite environment provided by Lando. Prefix all commands with `lando`:

```bash
lando composer test                                     # Run all tests
lando composer test -- tests/Unit/CollectionsTest.php   # Single file
lando composer test -- --filter=test_name               # Single test method
```

All tests extend `Tests\TestCase` which extends `\WP_UnitTestCase`. See `tests/bootstrap.php` for setup.

### PHP Linting (Laravel Pint)

```bash
composer standards              # Check
composer fix                    # Auto-fix
```

### JavaScript / CSS

```bash
npm run build                   # Production build (Vite)
npm run watch                   # Dev server with HMR
npm run lint                    # Lint JS + CSS
npm run lint:scripts            # ESLint only
npm run lint:styles             # Stylelint only
npm run fix:scripts             # Auto-fix JS
npm run fix:styles              # Auto-fix CSS
```

Node >= 22 (see `.nvmrc`). Sources in `assets/src/`, output in `assets/dist/`. Do not edit `assets/dist/` directly.

## Autoloading

PSR-4 via Composer: `PressbooksMemorious\` maps to `src/`.

```
PressbooksMemorious\Search\TypesenseClient  -> src/Search/TypesenseClient.php
PressbooksMemorious\Admin\SearchAdmin       -> src/Admin/SearchAdmin.php
PressbooksMemorious\Cli\MemoriousCommand      -> src/Cli/MemoriousCommand.php
```

## Code Style

### Formatting

- **PHP**: Indent with **tabs**. WordPress-style spacing inside parentheses.
- **JSON, YAML, SCSS**: Indent with **2 spaces**.
- UTF-8, LF line endings, final newline, trim trailing whitespace (see `.editorconfig`).

```php
// Correct:
if ( ! empty( $settings['typesense_nodes'] ) ) {
    $client = TypesenseClient::fromSettings();
}
```

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Methods | camelCase (PSR-1) | `enqueueAdminAssets()`, `processJob()` |
| Classes | PascalCase | `TypesenseClient`, `IndexJobProcessor` |
| Test files | `*Test.php` | `CollectionsTest.php`, `KeyGeneratorTest.php` |
| Test methods | `test_descriptiveName` | `test_parse_nodes_single_node()` |
| CLI commands | kebab-case | `wp memorious reindex`, `wp memorious process-jobs` |

### Type Hints

Use PHP 8.3+ type features in all new code:
- Typed parameters and return types on all methods
- Typed properties where appropriate
- Union types when needed: `array|string`

## Architecture Patterns

### Service Registration

Services are wired in `Bootstrap::run()` which is called on `plugins_loaded`:

```php
Bootstrap::run();  // registers services, hooks, menus, scripts, indexing
```

The `SearchService` is the central coordinator. It wraps `TypesenseClient` and manages indexing enqueueing.

### Key Singletons / Entry Points

- `Bootstrap` — registers all hooks, menus, scripts, CLI commands
- `SearchService` — coordinates between client, indexers, and queue
- `IndexJobProcessor` — owns `processJob()` (the single implementation; `MemoriousCommand` delegates to it)
- `TypesenseClient` — wraps the Typesense PHP SDK; owns `parseNodes()` and `fromSettings()`
- `MemoriousCommand::doResetCollections()` — single implementation for collection reset (used by CLI and AJAX)

### Admin Pages

Network admin settings page registered via `add_submenu_page` on `network_admin_menu`. Hidden search results page registered on `admin_menu` with `read` capability. Both use Blade templates via the Pressbooks Container.

### Asset Pipeline

Uses `PressbooksFrontendTools\Assets` + Vite via pressbooks-build-tools. Single entry point `assets/src/scripts/pressbooks-memorious.js` imports CSS — Vite auto-extracts it. Config is passed to JS via `wp_localize_script('pressbooks-memorious', 'PBMemorious', $config)`.

### Security: Scoped API Keys

`KeyGenerator::generateSearchKey()` derives scoped API keys using HMAC-SHA256. Keys embed `filter_by` rules restricting results to the user's blogs. Never exposes the admin key to the browser. Keys are cached as WordPress transients.

## Important Rules

- **No webbook/frontend search** — admin-only for now
- **Single `processJob` implementation** — lives in `IndexJobProcessor`, not duplicated in `MemoriousCommand`
- **Node parsing** — use `TypesenseClient::parseNodes()`, not a local implementation
- **Collections reset** — use `MemoriousCommand::doResetCollections()`, not inline
- **Context is always `'admin'`** — no webbook context in `getConfig()` or JS

## Git Conventions

- **Default branch**: `dev`
- **PR targets**: `dev`
- **Branch naming**: `feat/description`, `fix/description`, `chore/description`
- **Commit messages**: [Conventional Commits](https://www.conventionalcommits.org/) — present tense, imperative mood, first line <= 72 chars
- **Docs-only changes**: Include `[ci skip]` in commit body

## WP-CLI Commands

```bash
wp memorious reindex [<blog_id>]           # Queue reindex for one or all books
wp memorious process-jobs [<limit>]        # Process pending indexing jobs (default 50)
wp memorious create-collections            # Create Typesense collections (no delete)
wp memorious reset-collections             # Delete and recreate collections
wp memorious reindex-reset [<blog_id>]     # Reset collections + clear queue + full reindex
wp memorious job-status                    # Show pending/processing/completed/failed counts
```

## File Structure

```
pressbooks-memorious.php                    # Plugin entry point
src/
├── Bootstrap.php                        # Service registration, hooks
├── Admin/
│   ├── SearchAdmin.php                  # Settings page, AJAX, hidden results page
│   ├── SearchBar.php                    # Admin bar node, asset enqueueing
│   └── SearchHealthCheck.php            # Typesense health check (not wired)
├── Api/
│   └── SearchEndpoint.php              # REST API /pressbooks-memorious/v1/search
├── Cli/
│   └── MemoriousCommand.php               # WP-CLI commands
├── Database/
│   ├── Migration.php
│   └── Migrations/000001_*.php
├── Indexing/
│   ├── IndexerInterface.php
│   ├── IndexJobProcessor.php            # Queue + processJob (single source of truth)
│   └── Indexers/
│       ├── BooksIndexer.php
│       ├── ContributorsIndexer.php
│       └── SectionsIndexer.php
├── Search/
│   ├── Collections.php                  # Schema definitions for 3 collections
│   ├── KeyGenerator.php                 # Scoped key derivation (HMAC)
│   ├── SearchService.php                # Central coordination
│   └── TypesenseClient.php              # PHP client wrapper + parseNodes()
└── Interfaces/
    └── MigrationInterface.php
assets/
├── memorious.png
├── src/                                 # Edit here
│   ├── scripts/pressbooks-memorious.js
│   └── styles/pressbooks-memorious.css
└── dist/                                # Vite output (do not edit)
resources/views/
├── admin/settings.blade.php
└── search-results.blade.php
tests/
├── Unit/
│   ├── CollectionsTest.php
│   ├── KeyGeneratorTest.php
│   └── SearchBarTest.php
├── TestCase.php
└── bootstrap.php
```
