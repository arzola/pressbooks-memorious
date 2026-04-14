# Rename to pressbooks-memorious Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the plugin from `pressbooks-beacon` to `pressbooks-memorious` — all namespaces, identifiers, CLI commands, CSS, JS, DB table names, option keys, hooks, text domains, file names, and documentation.

**Architecture:** This is a global find-and-replace rename across ~35 source files. No new features or behavior changes. The approach is layered: first config files, then PHP source (namespace + identifiers), then frontend assets (JS/CSS), then blade templates, then documentation. Database table and option names use new names (fresh start — no migration of old data). A new migration file creates the renamed table; the old table is left behind.

**Tech Stack:** PHP 8.3, WordPress multisite, Typesense, Blade templates, Vite + JS + CSS

---

## Rename Mapping Reference

| Old | New |
|---|---|
| `pressbooks-beacon` (slug/text domain) | `pressbooks-memorious` |
| `PressbooksBeacon\` (PHP namespace) | `PressbooksMemorious\` |
| `BeaconCommand` (class) | `MemoriousCommand` |
| `BeaconCommand.php` (file) | `MemoriousCommand.php` |
| `beacon` (WP-CLI namespace) | `memorious` |
| `pb_beacon_settings` (option) | `pb_memorious_settings` |
| `pb_beacon_search` (page slug) | `pb_memorious_search` |
| `pb_beacon_key_` (transient prefix) | `pb_memorious_key_` |
| `pressbooks_beacon_index_jobs` (table) | `pressbooks_memorious_index_jobs` |
| `pb_beacon_*` (hooks) | `pb_memorious_*` |
| `pb-beacon-*` (CSS/HTML IDs, admin slugs) | `pb-memorious-*` |
| `--beacon-*` (CSS custom properties) | `--memorious-*` |
| `.pb-beacon-*` (CSS classes) | `.pb-memorious-*` |
| `.beacon-*` (CSS classes) | `.memorious-*` |
| `PBBeacon` (JS global) | `PBMemorious` |
| `pressbooks-beacon.js` / `.css` | `pressbooks-memorious.js` / `.css` |
| `beacon.png` | `memorious.png` |
| `Beacon\Search` (container key) | `Memorious\Search` |
| `pressbooks-beacon/v1` (REST) | `pressbooks-memorious/v1` |
| `pb-beacon-settings` (menu slug) | `pb-memorious-settings` |

---

### Task 1: Config files — composer.json, package.json, vite.config.js, phpunit.xml

**Files:**
- Modify: `composer.json`
- Modify: `package.json`
- Modify: `vite.config.js`
- Modify: `phpunit.xml`

- [ ] **Step 1: Update composer.json**

Replace these values:
- Line 2: `"name": "pressbooks/pressbooks-beacon"` → `"pressbooks/pressbooks-memorious"`
- Line 6: `"homepage": "https://github.com/pressbooks/pressbooks-beacon"` → `"https://github.com/pressbooks/pressbooks-memorious"`
- Line 22: `"issues": "https://github.com/pressbooks/pressbooks-beacon/issues/"` → `"https://github.com/pressbooks/pressbooks-memorious/issues/"`
- Line 23: `"source": "https://github.com/pressbooks/pressbooks-beacon/"` → `"https://github.com/pressbooks/pressbooks-memorious/"`
- Line 33: `"PressbooksBeacon\\": "src/"` → `"PressbooksMemorious\\": "src/"`

- [ ] **Step 2: Update package.json**

Replace:
- Line 2: `"name": "pressbooks-beacon"` → `"pressbooks-memorious"`
- Line 16: URL in `repository.url` → `pressbooks-memorious.git`
- Line 27: `bugs.url` → `pressbooks-memorious/issues`
- Line 29: `homepage` → `pressbooks-memorious#readme`

- [ ] **Step 3: Update vite.config.js**

Change line 6:
```js
app: resolve(__dirname, 'assets/src/scripts/pressbooks-memorious.js'),
```

- [ ] **Step 4: Update phpunit.xml**

Change line 18:
```xml
<testsuite name="Pressbooks Memorious">
```

- [ ] **Step 5: Commit**

```bash
git add composer.json package.json vite.config.js phpunit.xml
git commit -m "refactor: rename config files from beacon to memorious"
```

---

### Task 2: Plugin entry point — pressbooks-beacon.php → pressbooks-memorious.php

**Files:**
- Rename: `pressbooks-beacon.php` → `pressbooks-memorious.php`

- [ ] **Step 1: Rename and update the plugin entry point**

Rename the file, then update its contents:

```php
<?php

/**
 * Plugin Name: Pressbooks Memorious
 * Plugin URI: https://pressbooks.org
 * Requires at least: 6.8
 * Requires Plugins: pressbooks
 * Description: Fast, faceted search for Pressbooks networks powered by Typesense.
 * x-release-please-start-version
 * Version: 0.1.0
 * x-release-please-end
 * Author: Pressbooks (Book Oven Inc.)
 * Author URI: https://pressbooks.org
 * Requires PHP: 8.3
 * Pressbooks tested up to: 6.16.0
 * Text Domain: pressbooks-memorious
 * License: GPL v3 or later
 * Network: True
 */

use PressbooksMemorious\Bootstrap;
use PressbooksMemorious\Database\Migration;

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(__FILE__, [Migration::class, 'migrate']);

add_action('plugins_loaded', [Bootstrap::class, 'run']);
```

- [ ] **Step 2: Commit**

```bash
git add pressbooks-memorious.php
git rm pressbooks-beacon.php
git commit -m "refactor: rename plugin entry point to pressbooks-memorious.php"
```

---

### Task 3: PHP namespace rename — all src/ files

**Files:**
- Modify: all 18 PHP source files in `src/`

This is a global namespace replacement across all source files. Every file in `src/` that has `namespace PressbooksBeacon` or `use PressbooksBeacon` needs updating.

- [ ] **Step 1: Replace namespace declaration in all src/ files**

In every PHP file under `src/`, replace:
- `namespace PressbooksBeacon\` → `namespace PressbooksMemorious\`
- `use PressbooksBeacon\` → `use PressbooksMemorious\`

Affected files (18 total):
- `src/Bootstrap.php` — also update `PressbooksBeacon` in Blade namespace (line 39) and Container key
- `src/Admin/SearchAdmin.php`
- `src/Admin/SearchBar.php`
- `src/Admin/SearchHealthCheck.php`
- `src/Api/SearchEndpoint.php`
- `src/Cli/BeaconCommand.php` — will be renamed in Task 4
- `src/Database/Migration.php`
- `src/Database/Migrations/000001_create_search_index_jobs_table.php`
- `src/Indexing/IndexerInterface.php`
- `src/Indexing/IndexJobProcessor.php`
- `src/Indexing/Indexers/BooksIndexer.php`
- `src/Indexing/Indexers/ContributorsIndexer.php`
- `src/Indexing/Indexers/SectionsIndexer.php`
- `src/Interfaces/MigrationInterface.php`
- `src/Search/Collections.php`
- `src/Search/KeyGenerator.php`
- `src/Search/SearchService.php`
- `src/Search/TypesenseClient.php`

- [ ] **Step 2: Commit**

```bash
git add src/
git commit -m "refactor: rename PHP namespace from PressbooksBeacon to PressbooksMemorious"
```

---

### Task 4: Rename BeaconCommand → MemoriousCommand

**Files:**
- Rename: `src/Cli/BeaconCommand.php` → `src/Cli/MemoriousCommand.php`

- [ ] **Step 1: Rename the file and update the class**

Rename `src/Cli/BeaconCommand.php` to `src/Cli/MemoriousCommand.php`. Inside the file, rename:
- `class BeaconCommand` → `class MemoriousCommand`

Then update `src/Bootstrap.php`:
- `use PressbooksMemorious\Cli\BeaconCommand;` → `use PressbooksMemorious\Cli\MemoriousCommand;`
- `$cmd = BeaconCommand::class;` → `$cmd = MemoriousCommand::class;`
- All `\WP_CLI::add_command('beacon ...', [$cmd, ...])` → `'memorious ...'`

And update `src/Admin/SearchAdmin.php`:
- `\PressbooksMemorious\Cli\BeaconCommand::doResetCollections()` → `\PressbooksMemorious\Cli\MemoriousCommand::doResetCollections()`

- [ ] **Step 2: Commit**

```bash
git add src/Cli/MemoriousCommand.php src/Bootstrap.php src/Admin/SearchAdmin.php
git rm src/Cli/BeaconCommand.php
git commit -m "refactor: rename BeaconCommand to MemoriousCommand"
```

---

### Task 5: WP option keys, hooks, and DB table names in PHP source

**Files:**
- Modify: `src/Bootstrap.php`
- Modify: `src/Admin/SearchAdmin.php`
- Modify: `src/Admin/SearchBar.php`
- Modify: `src/Admin/SearchHealthCheck.php`
- Modify: `src/Indexing/IndexJobProcessor.php`
- Modify: `src/Search/KeyGenerator.php`
- Modify: `src/Search/SearchService.php`
- Modify: `src/Search/Collections.php`
- Modify: `src/Search/TypesenseClient.php`
- Modify: `src/Database/Migrations/000001_create_search_index_jobs_table.php`

- [ ] **Step 1: Replace option key `pb_beacon_settings` → `pb_memorious_settings`**

In every file containing `pb_beacon_settings`, replace all occurrences:
- `get_site_option('pb_beacon_settings'` → `get_site_option('pb_memorious_settings'`
- `update_site_option('pb_beacon_settings'` → `update_site_option('pb_memorious_settings'`

Files: Bootstrap.php (3), SearchAdmin.php (3), SearchBar.php (3), IndexJobProcessor.php (2), KeyGenerator.php (1), SearchHealthCheck.php (1), TypesenseClient.php (1)

- [ ] **Step 2: Replace DB table name `pressbooks_beacon_index_jobs` → `pressbooks_memorious_index_jobs`**

In every file containing `pressbooks_beacon_index_jobs`, replace all occurrences.

Files: IndexJobProcessor.php (10), MemoriousCommand.php (2), SearchHealthCheck.php (1)

- [ ] **Step 3: Replace transient prefix `pb_beacon_key_` → `pb_memorious_key_`**

File: `src/Search/KeyGenerator.php` line 13:
```php
$cacheKey = "pb_memorious_key_{$userId}_" . md5(json_encode($blogIds));
```

- [ ] **Step 4: Replace WordPress hook names**

Replace all `pb_beacon_` hook prefixes with `pb_memorious_`:

| Old hook | New hook | File(s) |
|---|---|---|
| `pb_beacon_index_processor` | `pb_memorious_index_processor` | IndexJobProcessor.php |
| `pb_beacon_job_failed` | `pb_memorious_job_failed` | IndexJobProcessor.php |
| `pb_beacon_indexers` | `pb_memorious_indexers` | SearchService.php, IndexJobProcessor.php |
| `pb_beacon_collections` | `pb_memorious_collections` | Collections.php |
| `pb_beacon_reindex_started` | `pb_memorious_reindex_started` | IndexJobProcessor.php |
| `pb_beacon_reindex_completed` | `pb_memorious_reindex_completed` | IndexJobProcessor.php |
| `pb_beacon_save_settings` | `pb_memorious_save_settings` | SearchAdmin.php |
| `pb_beacon_reindex_all` | `pb_memorious_reindex_all` | SearchAdmin.php |
| `pb_beacon_create_collections` | `pb_memorious_create_collections` | SearchAdmin.php |
| `pb_beacon_admin` | `pb_memorious_admin` | SearchAdmin.php |

- [ ] **Step 5: Replace admin page slugs and nonce actions**

In SearchAdmin.php:
- `'pb-beacon-settings'` → `'pb-memorious-settings'`
- `'pb_beacon_search'` → `'pb_memorious_search'`
- `'admin_action_pb_beacon_save_settings'` → `'admin_action_pb_memorious_save_settings'`

In SearchBar.php:
- `'pb-beacon-search'` (admin bar node ID) → `'pb-memorious-search'`

- [ ] **Step 6: Replace Container service key**

In Bootstrap.php:
- `Container::set('Beacon\Search'` → `Container::set('Memorious\Search'`
- `Container::get('Beacon\Search')` → `Container::get('Memorious\Search')`

- [ ] **Step 7: Replace Blade namespace**

In Bootstrap.php:
- `'PressbooksBeacon'` → `'PressbooksMemorious'` (in `addNamespace` call)

In SearchAdmin.php:
- `'PressbooksBeacon::admin.settings'` → `'PressbooksMemorious::admin.settings'`
- `'PressbooksBeacon::search-results'` → `'PressbooksMemorious::search-results'`

- [ ] **Step 8: Replace REST API namespace**

In SearchEndpoint.php:
- `'pressbooks-beacon/v1'` → `'pressbooks-memorious/v1'`

- [ ] **Step 9: Replace text domain in all PHP files**

In every `__()`, `esc_html__()`, `esc_attr__()`, `sprintf()` call:
- `'pressbooks-beacon'` → `'pressbooks-memorious'`

- [ ] **Step 10: Update migration file table name**

In `src/Database/Migrations/000001_create_search_index_jobs_table.php`:
- `'pressbooks_beacon_index_jobs'` → `'pressbooks_memorious_index_jobs'` (2 occurrences)

- [ ] **Step 11: Replace script handle and JS global reference**

In SearchBar.php:
- `new Assets('pressbooks-beacon'` → `new Assets('pressbooks-memorious'`
- `'assets/src/scripts/pressbooks-beacon.js'` → `'assets/src/scripts/pressbooks-memorious.js'`
- `'pressbooks-beacon'` (enqueue handle) → `'pressbooks-memorious'`
- `wp_localize_script('pressbooks-beacon', 'PBBeacon'` → `wp_localize_script('pressbooks-memorious', 'PBMemorious'`

- [ ] **Step 12: Replace resultsPageUrl in SearchBar.php**

In SearchBar.php `getConfig()`:
- `'admin.php?page=pb_beacon_search'` → `'admin.php?page=pb_memorious_search'`

- [ ] **Step 13: Commit**

```bash
git add src/
git commit -m "refactor: rename all beacon identifiers to memorious in PHP source"
```

---

### Task 6: Update tests

**Files:**
- Modify: `tests/TestCase.php`
- Modify: `tests/Unit/SearchBarTest.php`
- Modify: `tests/Unit/CollectionsTest.php`
- Modify: `tests/Unit/KeyGeneratorTest.php`

- [ ] **Step 1: Update namespace imports in test files**

Replace `PressbooksBeacon\` → `PressbooksMemorious\` in all test files.

`tests/TestCase.php`:
```php
use PressbooksMemorious\Bootstrap;
use PressbooksMemorious\Database\Migration;
```

`tests/Unit/SearchBarTest.php`:
```php
use PressbooksMemorious\Admin\SearchBar;
use PressbooksMemorious\Search\TypesenseClient;
```

`tests/Unit/CollectionsTest.php`:
```php
use PressbooksMemorious\Search\Collections;
```

`tests/Unit/KeyGeneratorTest.php`:
```php
use PressbooksMemorious\Search\KeyGenerator;
```

- [ ] **Step 2: Update option key in SearchBarTest.php**

Line 61:
```php
update_site_option('pb_memorious_settings', [
```

- [ ] **Step 3: Update hook name in CollectionsTest.php**

Line 70:
```php
add_filter('pb_memorious_collections', function (array $collections) {
```

- [ ] **Step 4: Commit**

```bash
git add tests/
git commit -m "refactor: rename beacon to memorious in test files"
```

---

### Task 7: Frontend assets — rename and update JS

**Files:**
- Rename: `assets/src/scripts/pressbooks-beacon.js` → `assets/src/scripts/pressbooks-memorious.js`
- Rename: `assets/src/styles/pressbooks-beacon.css` → `assets/src/styles/pressbooks-memorious.css`
- Rename: `assets/beacon.png` → `assets/memorious.png`

- [ ] **Step 1: Rename the JS file**

Rename `assets/src/scripts/pressbooks-beacon.js` → `assets/src/scripts/pressbooks-memorious.js`.

Replace all occurrences in the file:
- `PBBeacon` → `PBMemorious` (all occurrences: lines 1, 9, 13, 15, 34, 118, 265)
- `'Beacon search error:'` → `'Memorious search error:'` (line 162)
- All `pb-beacon-*` DOM IDs/classes → `pb-memorious-*`:
  - `pb-beacon-icon-btn` → `pb-memorious-icon-btn`
  - `pb-beacon-search-bar` → `pb-memorious-search-bar`
  - `pb-beacon-search-input` → `pb-memorious-search-input`
  - `pb-beacon-hint` → `pb-memorious-hint`
  - `pb-beacon-dropdown` → `pb-memorious-dropdown`
  - `pb-beacon-group` → `pb-memorious-group`
  - `pb-beacon-group-label` → `pb-memorious-group-label`
  - `pb-beacon-hit` → `pb-memorious-hit`
  - `pb-beacon-hit--contributor` → `pb-memorious-hit--contributor`
  - `pb-beacon-hit--book` → `pb-memorious-hit--book`
  - `pb-beacon-hit--section` → `pb-memorious-hit--section`
  - `pb-beacon-hit-type` → `pb-memorious-hit-type`
  - `pb-beacon-hit-body` → `pb-memorious-hit-body`
  - `pb-beacon-hit-title` → `pb-memorious-hit-title`
  - `pb-beacon-hit-meta` → `pb-memorious-hit-meta`
  - `pb-beacon-hit-snippet` → `pb-memorious-hit-snippet`
  - `pb-beacon-empty` → `pb-memorious-empty`
  - `pb-beacon-see-all` → `pb-memorious-see-all`
  - `pb-beacon-skeleton` → `pb-memorious-skeleton`
  - `pb-beacon-skeleton-item` → `pb-memorious-skeleton-item`
  - `pb-beacon-skeleton-line` → `pb-memorious-skeleton-line`
  - `pb-beacon-skeleton-meta` → `pb-memorious-skeleton-meta`
  - `pb-beacon-skeleton-text` → `pb-memorious-skeleton-text`
  - `pb-beacon-result` → `pb-memorious-result`
  - `pb-beacon-result-meta` → `pb-memorious-result-meta`
  - `pb-beacon-result-snippet` → `pb-memorious-result-snippet`
  - `pb-beacon-search-page` → `pb-memorious-search-page`
  - `pb-beacon-searchbox` → `pb-memorious-searchbox`
  - `pb-beacon-stats` → `pb-memorious-stats`
  - `pb-beacon-hits` → `pb-memorious-hits`
  - `pb-beacon-pagination` → `pb-memorious-pagination`
  - `pb-beacon-filter-post-type` → `pb-memorious-filter-post-type`
  - `pb-beacon-filter-book` → `pb-memorious-filter-book`
  - `pb-beacon-filter-authors` → `pb-memorious-filter-authors`
  - `pb-beacon-filter-license` → `pb-memorious-filter-license`
  - `pb-beacon-results` → `pb-memorious-results`
  - `#wp-admin-bar-pb-beacon-search` → `#wp-admin-bar-pb-memorious-search`
- Update the CSS import path:
  - `'../styles/pressbooks-beacon.css'` → `'../styles/pressbooks-memorious.css'`

- [ ] **Step 2: Rename the CSS file**

Rename `assets/src/styles/pressbooks-beacon.css` → `assets/src/styles/pressbooks-memorious.css`.

Replace all occurrences in the file:
- `--beacon-` → `--memorious-` (all CSS custom properties: bg, bg-raised, ink, ink-muted, ink-faint, accent, accent-hover, gold, gold-faint, rule, rule-strong, highlight, radius, serif, sans, dropdown-shadow, header-border)
- `.pb-beacon-` → `.pb-memorious-` (all CSS classes)
- `#pb-beacon-` → `#pb-memorious-` (all CSS IDs)
- `.beacon-` → `.memorious-` (e.g., `.beacon-page-header`, `.beacon-filter-heading`)
- `#wp-admin-bar-pb-beacon-search` → `#wp-admin-bar-pb-memorious-search`
- `@keyframes beacon-shimmer` → `@keyframes memorious-shimmer`

- [ ] **Step 3: Rename beacon.png → memorious.png**

```bash
git mv assets/beacon.png assets/memorious.png
```

- [ ] **Step 4: Delete old dist/ and commit**

```bash
rm -rf assets/dist/
git add assets/
git commit -m "refactor: rename frontend assets from beacon to memorious"
```

---

### Task 8: Blade templates

**Files:**
- Modify: `resources/views/admin/settings.blade.php`
- Modify: `resources/views/search-results.blade.php`

- [ ] **Step 1: Update settings.blade.php**

Replace all occurrences:
- `'pressbooks-beacon'` (text domain) → `'pressbooks-memorious'` (16 occurrences)
- `'pb_beacon_save_settings'` → `'pb_memorious_save_settings'` (nonce field + hidden input)
- `'admin_action_pb_beacon_save_settings'` → `'admin_action_pb_memorious_save_settings'`
- `pb_beacon_settings[` → `pb_memorious_settings[` (all form input names)
- `pb-beacon-reindex-all` → `pb-memorious-reindex-all` (button ID)
- `pb-beacon-create-collections` → `pb-memorious-create-collections` (button ID)
- `pb_beacon_reindex_all` → `pb_memorious_reindex_all` (AJAX action in fetch URL)
- `pb_beacon_create_collections` → `pb_memorious_create_collections` (AJAX action in fetch URL)
- `pressbooks_beacon_index_jobs` → `pressbooks_memorious_index_jobs` (table name)
- `\PressbooksBeacon\Admin\SearchAdmin` → `\PressbooksMemorious\Admin\SearchAdmin` (Blade @php reference)

- [ ] **Step 2: Update search-results.blade.php**

Replace all occurrences:
- `'pressbooks-beacon'` (text domain) → `'pressbooks-memorious'` (5 occurrences)
- `pb-beacon-search-page` → `pb-memorious-search-page` (ID)
- `beacon-page-header` → `memorious-page-header` (class)
- `beacon-filter-heading` → `memorious-filter-heading` (class)
- `pb-beacon-filters` → `pb-memorious-filters`
- `pb-beacon-filter-post-type` → `pb-memorious-filter-post-type`
- `pb-beacon-filter-book` → `pb-memorious-filter-book`
- `pb-beacon-filter-authors` → `pb-memorious-filter-authors`
- `pb-beacon-filter-license` → `pb-memorious-filter-license`
- `pb-beacon-results` → `pb-memorious-results`
- `pb-beacon-searchbox` → `pb-memorious-searchbox`
- `pb-beacon-stats` → `pb-memorious-stats`
- `pb-beacon-hits` → `pb-memorious-hits`
- `pb-beacon-pagination` → `pb-memorious-pagination`

- [ ] **Step 3: Commit**

```bash
git add resources/views/
git commit -m "refactor: rename beacon to memorious in blade templates"
```

---

### Task 9: Documentation — README.md, AGENTS.md, CONTRIBUTING.md

**Files:**
- Modify: `README.md`
- Modify: `AGENTS.md`
- Modify: `CONTRIBUTING.md`

- [ ] **Step 1: Update README.md**

Replace all occurrences:
- `Pressbooks Beacon` → `Pressbooks Memorious` (plugin name)
- `pressbooks-beacon` → `pressbooks-memorious` (slug, URLs, text domain, CLI examples)
- `PressbooksBeacon\` → `PressbooksMemorious\` (namespace examples)
- `BeaconCommand.php` → `MemoriousCommand.php`
- `beacon.png` → `memorious.png`
- `pressbooks-beacon.js` → `pressbooks-memorious.js`
- `pressbooks-beacon.css` → `pressbooks-memorious.css`
- `wp beacon` → `wp memorious` (CLI command examples)
- `pb_beacon_collections` → `pb_memorious_collections` (filter example)
- `Beacon Search` → `Memorious Search` (admin menu reference)
- "Why Beacon?" → "Why Memorious?"
- `Beacon never exposes` → `Memorious never exposes`

- [ ] **Step 2: Update AGENTS.md**

Replace all occurrences:
- `Pressbooks Beacon` → `Pressbooks Memorious`
- `pressbooks-beacon` → `pressbooks-memorious`
- `PressbooksBeacon\` → `PressbooksMemorious\`
- `BeaconCommand` → `MemoriousCommand`
- `BeaconCommand.php` → `MemoriousCommand.php`
- `wp beacon` → `wp memorious`
- `pb_beacon_*` → `pb_memorious_*` (settings, hooks)
- `beacon.png` → `memorious.png`
- `pressbooks-beacon.js` → `pressbooks-memorious.js`
- `pressbooks-beacon.css` → `pressbooks-memorious.css`
- `pb-beacon-settings` → `pb-memorious-settings`
- `Container::set('Beacon\Search'` → `Container::set('Memorious\Search'`
- `'PressbooksBeacon::admin.settings'` → `'PressbooksMemorious::admin.settings'`
- `'PressbooksBeacon::search-results'` → `'PressbooksMemorious::search-results'`

Update the File Structure section to reflect all renamed files.

- [ ] **Step 3: Update CONTRIBUTING.md**

Replace all occurrences:
- `Pressbooks Beacon` → `Pressbooks Memorious`
- `pressbooks-beacon` → `pressbooks-memorious`
- `PressbooksBeacon\` → `PressbooksMemorious\`
- `BeaconCommand` → `MemoriousCommand`

- [ ] **Step 4: Commit**

```bash
git add README.md AGENTS.md CONTRIBUTING.md
git commit -m "docs: rename beacon to memorious in documentation"
```

---

### Task 10: Rebuild assets and verify

**Files:**
- Regenerate: `assets/dist/`

- [ ] **Step 1: Run composer dump-autoload**

```bash
composer dump-autoload
```

- [ ] **Step 2: Install npm dependencies and build**

```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

- [ ] **Step 3: Run linters**

```bash
composer standards
npm run lint
```

- [ ] **Step 4: Run tests**

```bash
lando composer test
```

- [ ] **Step 5: Commit build artifacts**

```bash
git add assets/dist/
git commit -m "build: rebuild dist assets for memorious rename"
```

---

### Task 11: Root directory rename (manual step)

This is a manual step since renaming the root directory of a Git repo requires renaming outside the repo:

- [ ] **Step 1: Rename the directory**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins
mv pressbooks-beacon pressbooks-memorious
cd pressbooks-memorious
```

No commit needed — this is a filesystem rename, not a Git operation (the repo root changes).
