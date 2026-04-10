<?php

namespace PressbooksBorges\Admin;

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
        add_action('admin_action_pb_borges_save_settings', [$obj, 'saveSettings']);
        add_action('wp_ajax_pb_borges_reindex_all', [$obj, 'ajaxReindexAll']);
        add_action('wp_ajax_pb_borges_create_collections', [$obj, 'ajaxCreateCollections']);
    }

    public static function getDefaults(): array
    {
        return [
            'typesense_nodes' => '',
            'typesense_admin_key' => '',
            'typesense_search_key' => '',
            'enabled_admin' => true,
            'enabled_webbook' => true,
            'index_private_books' => true,
            'index_draft_content' => false,
            'max_retries' => 3,
            'batch_size' => 50,
        ];
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'settings.php',
            __('Pressbooks Borges Search', 'pressbooks-borges'),
            __('Borges Search', 'pressbooks-borges'),
            'manage_network_options',
            'pb-borges-settings',
            [$this, 'renderSettingsPage']
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
            'enabled_webbook' => ! empty($input['enabled_webbook']),
            'index_private_books' => ! empty($input['index_private_books']),
            'index_draft_content' => ! empty($input['index_draft_content']),
            'max_retries' => absint($input['max_retries'] ?? $defaults['max_retries']),
            'batch_size' => absint($input['batch_size'] ?? $defaults['batch_size']),
        ];
    }

    public function saveSettings(): void
    {
        if (! check_admin_referer('pb_borges_save_settings')) {
            wp_die(esc_html__('Nonce verification failed.', 'pressbooks-borges'));
        }

        if (! current_user_can('manage_network_options')) {
            wp_die(esc_html__('Unauthorized.', 'pressbooks-borges'));
        }

        $input = $_POST['pb_borges_settings'] ?? [];
        $sanitized = $this->sanitizeSettings($input);
        update_site_option('pb_borges_settings', $sanitized);

        wp_safe_redirect(add_query_arg([
            'page' => 'pb-borges-settings',
            'updated' => '1',
        ], network_admin_url('settings.php')));
        exit;
    }

    public function renderSettingsPage(): void
    {
        echo \Pressbooks\Container::get('Blade')->render('PressbooksBorges::admin.settings', [
            'settings' => get_site_option('pb_borges_settings', self::getDefaults()),
            'defaults' => self::getDefaults(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pb_borges_admin'),
        ]);
    }

    public function ajaxReindexAll(): void
    {
        check_ajax_referer('pb_borges_admin');

        if (! current_user_can('manage_network_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $search = \Pressbooks\Container::get('Borges\Search');
        $sites = get_sites(['number' => 0]);

        foreach ($sites as $site) {
            $search->enqueueReindexBook((int) $site->blog_id);
        }

        wp_send_json_success([
            'message' => sprintf(__('Queued %d books for reindexing.', 'pressbooks-borges'), count($sites)),
            'count' => count($sites),
        ]);
    }

    public function ajaxCreateCollections(): void
    {
        check_ajax_referer('pb_borges_admin');

        if (! current_user_can('manage_network_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        try {
            $search = \Pressbooks\Container::get('Borges\Search');
            $search->ensureCollections();
            wp_send_json_success([
                'message' => __('Collections created successfully.', 'pressbooks-borges'),
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
