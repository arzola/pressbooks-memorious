<?php

namespace PressbooksBorges\Admin;

use PressbooksBorges\Search\KeyGenerator;

class SearchBar
{
    public static function init(): void
    {
        add_action('admin_bar_menu', [self::class, 'addSearchBar'], 100);
    }

    public static function enqueueAssets(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueWebbookAssets']);
    }

    public static function addSearchBar(\WP_Admin_Bar $wpAdminBar): void
    {
        $settings = get_site_option('pb_borges_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return;
        }

        if (is_admin() && empty($settings['enabled_admin'])) {
            return;
        }

        if (! is_admin() && empty($settings['enabled_webbook'])) {
            return;
        }

        $wpAdminBar->add_node([
            'id' => 'pb-borges-search',
            'title' => '<input type="text" id="pb-borges-search-input" placeholder="' . esc_attr__('Search books and content...', 'pressbooks-borges') . '" />',
            'href' => '#',
        ]);
    }

    public static function enqueueAdminAssets(): void
    {
        $settings = get_site_option('pb_borges_settings', []);
        if (empty($settings['typesense_nodes']) || empty($settings['enabled_admin'])) {
            return;
        }

        self::doEnqueue('admin');
    }

    public static function enqueueWebbookAssets(): void
    {
        if (! function_exists('\\Pressbooks\\Book::isBook') || ! \Pressbooks\Book::isBook()) {
            return;
        }

        $settings = get_site_option('pb_borges_settings', []);
        if (empty($settings['typesense_nodes']) || empty($settings['enabled_webbook'])) {
            return;
        }

        self::doEnqueue('webbook');
    }

    private static function doEnqueue(string $context): void
    {
        $handle = 'pressbooks-borges';

        Vite\enqueue_asset(
            WP_PLUGIN_DIR . '/pressbooks-borges/dist',
            'resources/assets/js/pressbooks-borges.js',
            ['handle' => $handle]
        );

        $userId = get_current_user_id();
        $currentBlogId = $context === 'webbook' ? get_current_blog_id() : null;

        $config = self::getConfig($userId, $currentBlogId);

        wp_localize_script($handle, 'PBBorges', $config);
    }

    public static function getConfig(int $userId, ?int $currentBlogId): array
    {
        $settings = get_site_option('pb_borges_settings', []);

        $apiKey = $userId
            ? KeyGenerator::generateSearchKey($userId, $currentBlogId)
            : KeyGenerator::generateAnonymousKey(get_current_blog_id());

        return [
            'typesense' => [
                'nodes' => self::parseNodes($settings['typesense_nodes'] ?? ''),
                'apiKey' => $apiKey,
                'searchOnly' => true,
            ],
            'collections' => [
                'sections' => 'pb_sections',
                'books' => 'pb_books',
                'contributors' => 'pb_contributors',
            ],
            'context' => $currentBlogId !== null ? 'webbook' : 'admin',
            'currentBlogId' => $currentBlogId ?? get_current_blog_id(),
            'resultsPageUrl' => admin_url('admin.php?page=pb_borges_search'),
        ];
    }

    public static function parseNodes(string $nodesStr): array
    {
        if (empty($nodesStr)) {
            return [];
        }

        return array_map(function (string $node) {
            $parts = explode(':', $node, 3);
            return [
                'host' => $parts[0] ?? 'localhost',
                'port' => (int) ($parts[1] ?? 443),
                'protocol' => $parts[2] ?? 'https',
            ];
        }, array_filter(array_map('trim', explode(',', $nodesStr))));
    }
}
