<?php

namespace Tests\Unit;

use PressbooksBeacon\Admin\SearchBar;
use PressbooksBeacon\Search\TypesenseClient;
use Tests\TestCase;

class SearchBarTest extends TestCase
{
    public function test_parse_nodes_single_node(): void
    {
        $nodes = TypesenseClient::parseNodes('localhost:8108:http');

        $this->assertCount(1, $nodes);
        $this->assertEquals([
            'host' => 'localhost',
            'port' => 8108,
            'protocol' => 'http',
        ], $nodes[0]);
    }

    public function test_parse_nodes_multiple_nodes(): void
    {
        $nodes = TypesenseClient::parseNodes('node1:8108:http, node2:8108:http');

        $this->assertCount(2, $nodes);
        $this->assertEquals('node1', $nodes[0]['host']);
        $this->assertEquals('node2', $nodes[1]['host']);
    }

    public function test_parse_nodes_defaults(): void
    {
        $nodes = TypesenseClient::parseNodes('example.com');

        $this->assertCount(1, $nodes);
        $this->assertEquals([
            'host' => 'example.com',
            'port' => 443,
            'protocol' => 'https',
        ], $nodes[0]);
    }

    public function test_parse_nodes_empty_string(): void
    {
        $nodes = TypesenseClient::parseNodes('');

        $this->assertCount(0, $nodes);
    }

    public function test_parse_nodes_with_whitespace(): void
    {
        $nodes = TypesenseClient::parseNodes('  localhost:8108:http , ');

        $this->assertCount(1, $nodes);
        $this->assertEquals('localhost', $nodes[0]['host']);
    }

    public function test_get_config_returns_expected_keys(): void
    {
        update_site_option('pb_beacon_settings', [
            'typesense_nodes' => 'localhost:8108:http',
            'typesense_search_key' => 'test-search-key',
            'enabled_admin' => 1,
            'theme' => 'scholarly',
        ]);

        $config = SearchBar::getConfig(1);

        $this->assertArrayHasKey('typesense', $config);
        $this->assertArrayHasKey('collections', $config);
        $this->assertArrayHasKey('context', $config);
        $this->assertArrayHasKey('currentBlogId', $config);
        $this->assertArrayHasKey('theme', $config);
        $this->assertArrayHasKey('resultsPageUrl', $config);
        $this->assertEquals('admin', $config['context']);
        $this->assertEquals('scholarly', $config['theme']);
    }
}
