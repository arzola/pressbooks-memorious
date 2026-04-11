# Contributing to Pressbooks Beacon

Contributions are welcome and appreciated. Here's how to get started.

## Setup

```bash
git clone https://github.com/pressbooks/pressbooks-beacon.git
cd pressbooks-beacon
composer install
npm install
npm run build
```

For local development with Lando, see the full setup instructions in [README.md](README.md#local-development-lando).

## Development Workflow

1. Create a branch: `feat/description`, `fix/description`, or `chore/description`
2. Make your changes
3. Run the linters and tests (see below)
4. Submit a pull request against the `dev` branch

## Code Style

### PHP

- **PHP 8.3+** with typed parameters and return types on all new methods
- Indent with **tabs** (see `.editorconfig`)
- Linting via [Laravel Pint](https://github.com/laravel/pint):
  ```bash
  composer standards          # Check
  composer fix                # Auto-fix
  ```

### JavaScript / CSS

- Sources in `assets/src/` — do not edit `assets/dist/` directly
- Linting via pressbooks-build-tools (ESLint + Stylelint):
  ```bash
  npm run lint                # Check JS + CSS
  npm run fix:scripts         # Auto-fix JS
  npm run fix:styles          # Auto-fix CSS
  ```
- Build:
  ```bash
  npm run build               # Production build
  npm run watch               # Dev server with HMR
  ```

## Testing

Tests require a WordPress Multisite environment provided by Lando. Prefix all test commands with `lando`:

```bash
# All tests
lando composer test

# Single file
lando composer test -- tests/Unit/CollectionsTest.php

# Single method
lando composer test -- --filter test_parse_nodes_single_node
```

All tests extend `Tests\TestCase` (which extends `\WP_UnitTestCase`). Write tests for all new code — PRs that reduce coverage will not be merged.

## Architecture

- **PSR-4 autoloading**: `PressbooksBeacon\` maps to `src/`
- **No Controllers directory** — admin pages are handled by `SearchAdmin.php`, rendering via Blade templates in `resources/views/`
- **Single processJob implementation** in `IndexJobProcessor` — `BeaconCommand` delegates to it
- **Node parsing** is centralized in `TypesenseClient::parseNodes()`
- **Collections reset** is centralized in `BeaconCommand::doResetCollections()`
- **Asset pipeline**: `PressbooksFrontendTools\Assets` + Vite via pressbooks-build-tools. JS imports CSS; Vite auto-extracts it.

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/) — present tense, imperative mood, first line 72 chars max:

```
feat: Add glossary term indexing
fix: Resolve scoped key derivation for empty blog lists
chore: Update Typesense PHP client to 4.10
```

## Pull Requests

- Target the `dev` branch
- Include tests for any new functionality
- Run `composer standards` and `npm run lint` before pushing
- If changing CSS/JS, include a screenshot or GIF in the PR description when applicable
- Docs-only changes: include `[ci skip]` in the commit body

## Reporting Issues

Found a bug? Have a feature request? Please [open an issue](https://github.com/pressbooks/pressbooks-beacon/issues) with:

- Steps to reproduce (for bugs)
- Expected vs. actual behavior
- Your environment (PHP version, WordPress version, Typesense version)
