<?php

namespace PressbooksMemorious\Admin;

use PressbooksMemorious\Search\KeyGenerator;
use PressbooksMemorious\Search\TypesenseClient;
use PressbooksFrontendTools\Assets;
use PressbooksFrontendTools\AssetType;

class SearchBar
{
    public static function init(): void
    {
        add_action('admin_bar_menu', [self::class, 'addSearchBar'], 100);
    }

    public static function enqueueAssets(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
    }

    public static function addSearchBar(\WP_Admin_Bar $wpAdminBar): void
    {
        $settings = get_site_option('pb_memorious_settings', []);

        if (empty($settings['typesense_nodes']) || empty($settings['enabled_admin'])) {
            return;
        }

        $wpAdminBar->add_node([
            'id' => 'pb-memorious-search',
            'parent' => 'top-secondary',
            'title' => '<span class="pb-memorious-icon-btn" role="button" tabindex="0" aria-label="' . esc_attr__('Search', 'pressbooks-memorious') . '" aria-expanded="false" aria-controls="pb-memorious-search-bar"><i class="pb-heroicons pb-heroicons-outline_magnifying-glass"></i></span>',
            'href' => false,
            'meta' => [
                'tabindex' => 0,
            ],
        ]);
    }

    public static function enqueueAdminAssets(): void
    {
        $settings = get_site_option('pb_memorious_settings', []);
        if (empty($settings['typesense_nodes']) || empty($settings['enabled_admin'])) {
            return;
        }

        self::doEnqueue();
    }

    private static function doEnqueue(): void
    {
        $assets = new Assets('pressbooks-memorious', AssetType::PLUGIN);
        $assets->enqueue(
            'assets/src/scripts/pressbooks-memorious.js',
            'pressbooks-memorious',
        );

        $userId = get_current_user_id();
        $config = self::getConfig($userId);

        wp_localize_script('pressbooks-memorious', 'PBMemorious', $config);
    }

    public static function getConfig(int $userId): array
    {
        $settings = get_site_option('pb_memorious_settings', []);

        $apiKey = $userId
            ? KeyGenerator::generateSearchKey($userId)
            : KeyGenerator::generateSearchKey(0);

        return [
            'typesense' => [
                'nodes' => TypesenseClient::parseNodes($settings['typesense_nodes'] ?? ''),
                'apiKey' => $apiKey,
                'searchOnly' => true,
            ],
            'collections' => [
                'sections' => 'pb_sections',
                'books' => 'pb_books',
                'contributors' => 'pb_contributors',
            ],
            'context' => 'admin',
            'currentBlogId' => get_current_blog_id(),
            'blogIds' => $userId ? KeyGenerator::getUserBlogIds($userId) : [],
            'theme' => $settings['theme'] ?? 'scholarly',
            'resultsPageUrl' => admin_url('admin.php?page=pb_memorious_search'),
        ];
    }
}
