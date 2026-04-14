<?php

namespace PressbooksMemorious\Admin;

class SearchAdmin
{
    private static ?self $instance = null;

    public static function init(): self
    {
        if (! self::$instance) {
            self::$instance = new self;
            self::hooks(self::$instance);
        }
        return self::$instance;
    }

    public static function hooks(self $obj): void
    {
        add_action('network_admin_menu', [$obj, 'addMenu']);
        add_action('admin_menu', [$obj, 'addSearchResultsPage']);
        add_action('admin_action_pb_memorious_save_settings', [$obj, 'saveSettings']);
        add_action('wp_ajax_pb_memorious_reindex_all', [$obj, 'ajaxReindexAll']);
        add_action('wp_ajax_pb_memorious_create_collections', [$obj, 'ajaxCreateCollections']);
    }

    public static function getDefaults(): array
    {
        return [
            'typesense_nodes' => '',
            'typesense_admin_key' => '',
            'typesense_search_key' => '',
            'enabled_admin' => true,
            'index_private_books' => true,
            'index_draft_content' => false,
            'max_retries' => 3,
            'batch_size' => 50,
            'theme' => 'scholarly',
        ];
    }

    public static function getThemes(): array
    {
        return ['scholarly', 'modern', 'pressbooks'];
    }

    public static function getThemeLabels(): array
    {
        return [
            'scholarly' => __('Scholarly — warm paper, serif, burgundy accents', 'pressbooks-memorious'),
            'modern' => __('Modern — crisp, minimal, cool neutrals', 'pressbooks-memorious'),
            'pressbooks' => __('Pressbooks — matches PB admin, red accents, Karla + Spectral', 'pressbooks-memorious'),
        ];
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'settings.php',
            __('Pressbooks Memorious Search', 'pressbooks-memorious'),
            __('Memorious Search', 'pressbooks-memorious'),
            'manage_network_options',
            'pb-memorious-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function addSearchResultsPage(): void
    {
        add_submenu_page(
            null,
            __('Search Results', 'pressbooks-memorious'),
            __('Search', 'pressbooks-memorious'),
            'read',
            'pb_memorious_search',
            [$this, 'renderSearchResultsPage']
        );
    }

    public function sanitizeSettings(array $input): array
    {
        $defaults = self::getDefaults();

        return [
            'typesense_nodes' => sanitize_text_field($input['typesense_nodes'] ?? ''),
            'typesense_admin_key' => sanitize_text_field($input['typesense_admin_key'] ?? ''),
            'typesense_search_key' => sanitize_text_field($input['typesense_search_key'] ?? ''),
            'enabled_admin' => ! empty($input['enabled_admin']),
            'index_private_books' => ! empty($input['index_private_books']),
            'index_draft_content' => ! empty($input['index_draft_content']),
            'max_retries' => absint($input['max_retries'] ?? $defaults['max_retries']),
            'batch_size' => absint($input['batch_size'] ?? $defaults['batch_size']),
            'theme' => in_array($input['theme'] ?? '', self::getThemes(), true)
                ? $input['theme']
                : $defaults['theme'],
        ];
    }

    public function saveSettings(): void
    {
        if (! check_admin_referer('pb_memorious_save_settings')) {
            wp_die(esc_html__('Nonce verification failed.', 'pressbooks-memorious'));
        }

        if (! current_user_can('manage_network_options')) {
            wp_die(esc_html__('Unauthorized.', 'pressbooks-memorious'));
        }

        $input = $_POST['pb_memorious_settings'] ?? [];
        $sanitized = $this->sanitizeSettings($input);
        update_site_option('pb_memorious_settings', $sanitized);

        wp_safe_redirect(add_query_arg([
            'page' => 'pb-memorious-settings',
            'updated' => '1',
        ], network_admin_url('settings.php')));
        exit;
    }

    public function renderSettingsPage(): void
    {
        echo \Pressbooks\Container::get('Blade')->render('PressbooksMemorious::admin.settings', [
            'settings' => get_site_option('pb_memorious_settings', self::getDefaults()),
            'defaults' => self::getDefaults(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pb_memorious_admin'),
        ]);
    }

    public function renderSearchResultsPage(): void
    {
        \PressbooksMemorious\Admin\SearchBar::enqueueAdminAssets();

        echo '<div class="wrap">';
        echo \Pressbooks\Container::get('Blade')->render('PressbooksMemorious::search-results');
        echo '</div>';
    }

    public function ajaxReindexAll(): void
    {
        check_ajax_referer('pb_memorious_admin');

        if (! current_user_can('manage_network_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $search = new \PressbooksMemorious\Search\SearchService(
            \PressbooksMemorious\Search\TypesenseClient::fromSettings()
        );
        $sites = get_sites(['number' => 0]);

        foreach ($sites as $site) {
            $search->enqueueReindexBook((int) $site->blog_id);
        }

        wp_send_json_success([
            'message' => sprintf(__('Queued %d books for reindexing.', 'pressbooks-memorious'), count($sites)),
            'count' => count($sites),
        ]);
    }

    public function ajaxCreateCollections(): void
    {
        check_ajax_referer('pb_memorious_admin');

        if (! current_user_can('manage_network_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        try {
            \PressbooksMemorious\Cli\MemoriousCommand::doResetCollections();

            $search = new \PressbooksMemorious\Search\SearchService(
                \PressbooksMemorious\Search\TypesenseClient::fromSettings()
            );
            $search->ensureCollections();
            wp_send_json_success([
                'message' => __('Collections recreated successfully.', 'pressbooks-memorious'),
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
