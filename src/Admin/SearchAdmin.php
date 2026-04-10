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
        ]);
    }
}
