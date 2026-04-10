<?php

use PressbooksPluginScaffold\Interfaces\MigrationInterface;

return new class implements MigrationInterface {
    private readonly string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table = "{$wpdb->base_prefix}table_name";
    }

    public function up(): void
    {
        global $wpdb;

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (id)
) {$wpdb->get_charset_collate()}
SQL;

        $wpdb->query($sql);
    }
};
