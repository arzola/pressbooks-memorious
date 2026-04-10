<?php

namespace PressbooksBorges\Search;

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
        $settings = get_site_option('pb_borges_settings', []);

        $nodes = array_map(function (string $node) {
            [$host, $port, $protocol] = explode(':', $node, 3);
            return [
                'host' => $host,
                'port' => (int) $port,
                'protocol' => $protocol,
            ];
        }, explode(',', $settings['typesense_nodes'] ?? ''));

        return new self(
            nodes: $nodes,
            adminApiKey: $settings['typesense_admin_key'] ?? '',
            searchOnlyKey: $settings['typesense_search_key'] ?? null,
        );
    }
}
