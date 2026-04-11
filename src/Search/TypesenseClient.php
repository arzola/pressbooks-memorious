<?php

namespace PressbooksBeacon\Search;

use Typesense\Client;

class TypesenseClient
{
    private Client $client;
    private string $adminKey;
    private ?string $searchOnlyKey;

    public function __construct(
        array $nodes,
        string $adminApiKey,
        ?string $searchOnlyKey = null,
    ) {
        $this->adminKey = $adminApiKey;
        $this->searchOnlyKey = $searchOnlyKey;
        $this->client = new Client([
            'api_key' => $adminApiKey,
            'nodes' => $nodes,
            'connection_timeout_seconds' => 5,
            'retry_interval_seconds' => 1,
        ]);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAdminKey(): string
    {
        return $this->adminKey;
    }

    public function getSearchKey(): ?string
    {
        return $this->searchOnlyKey;
    }

    public static function fromSettings(): self
    {
        $settings = get_site_option('pb_beacon_settings', []);

        return new self(
            nodes: self::parseNodes($settings['typesense_nodes'] ?? ''),
            adminApiKey: $settings['typesense_admin_key'] ?? '',
            searchOnlyKey: $settings['typesense_search_key'] ?? null,
        );
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
