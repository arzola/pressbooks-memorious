# Pressbooks Plugin Scaffold

**Contributors:** conner_bw, greatislander, steelwagstaff, arzola, fdalcin \
**Tags:** pressbooks, plugin, scaffolding \
**Requires at least:** 6.5 \
**Tested up to:** 6.5 \
<!-- x-release-please-start-version -->
**Stable tag:** 0.1.0 \
<!-- x-release-please-end -->
**License:** GPLv3 or later \
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html

Scaffolding for a Pressbooks plugin.

## Description

This is not a plugin, but a tool that helps you scaffold a plugin.

## Installation

### Create Plugin

Run `composer create-project pressbooks/pressbooks-plugin-scaffold <your-plugin-slug>`.

Run `php configure.php` script to set up your newly created plugin. This will replace all `pressbooks-plugin-scaffold` and its variants throughout all the files.

### Optional Steps

Configure GitHub Action deploys (instructions to come).

Configure Transifex project and localization (instructions to come).

### Helpful Commands

`composer standards`: check PHP coding standards with Laravel Pint \
`composer fix`: fix PHP coding standards with Laravel Pint \
`composer test`: run unit tests with PHPUnit \
`composer readme`: generate a Markdown readme from readme.txt \
`npm run dev`:  build assets for development \
`npm run build`: build assets for distribution

## Directory Structure

### Controllers

Controllers are responsible for handling requests and returning responses. They are located in the `src/Controllers` directory.

### Database

Database migrations are located in the `src/Database/Migrations` directory.

### Views

Composed Views like WP_List_Table are located in the `src/Views` directory.

Blade templates are located in the `resources/views/{namespace}` directory.

### Models

Models are located in the `src/Models` directory.

### Changelog
Please see the [CHANGELOG](CHANGELOG.md) file for more information.
