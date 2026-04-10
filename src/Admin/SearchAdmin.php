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
        add_action('admin_init', [$obj, 'registerSettings']);
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

    public function registerSettings(): void
    {
        register_setting('pb_borges_settings_group', 'pb_borges_settings', [
            'default' => self::getDefaults(),
            'sanitize_callback' => [$this, 'sanitizeSettings'],
        ]);
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

    public function renderSettingsPage(): void
    {
        echo \Pressbooks\Container::get('Blade')->render('PressbooksBorges::admin.settings', [
            'settings' => get_site_option('pb_borges_settings', self::getDefaults()),
            'defaults' => self::getDefaults(),
        ]);
    }
}
