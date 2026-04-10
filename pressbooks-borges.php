<?php

/**
 * Plugin Name: Pressbooks Borges
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
 * Text Domain: pressbooks-borges
 * License: GPL v3 or later
 * Network: True
 */

use PressbooksBorges\Bootstrap;
use PressbooksBorges\Database\Migration;

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(__FILE__, [Migration::class, 'migrate']);

add_action('plugins_loaded', [Bootstrap::class, 'run']);
