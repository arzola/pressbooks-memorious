<?php

namespace PressbooksMemorious\Api;

use PressbooksMemorious\Search\TypesenseClient;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class SearchEndpoint
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route('pressbooks-memorious/v1', '/search', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'handleSearch'],
                'permission_callback' => [self::class, 'permissionCheck'],
            ],
        ]);
    }

    public static function permissionCheck(\WP_REST_Request $request): bool
    {
        return true;
    }

    public static function handleSearch(WP_REST_Request $request): WP_REST_Response
    {
        $q = $request->get_param('q');

        if (empty($q) || strlen($q) < 2) {
            return new WP_REST_Response([
                'code' => 'invalid_query',
                'message' => __('Query must be at least 2 characters.', 'pressbooks-memorious'),
            ], 400);
        }

        $collection = $request->get_param('collection') ?? 'all';
        $perPage = min((int) ($request->get_param('per_page') ?? 10), 50);
        $page = max((int) ($request->get_param('page') ?? 1), 1);

        $userId = get_current_user_id();
        $client = TypesenseClient::fromSettings();

        $validCollections = ['pb_sections', 'pb_books', 'pb_contributors'];

        if ($collection === 'all') {
            $targetCollections = $validCollections;
        } else {
            $targetCollections = in_array("pb_{$collection}", $validCollections, true)
                ? ["pb_{$collection}"]
                : $validCollections;
        }

        $searchRequests = [];
        foreach ($targetCollections as $col) {
            $searchRequests['searches'][] = [
                'collection' => $col,
                'q' => $q,
                'query_by' => $col === 'pb_sections' ? 'title,content,authors,book_title'
                    : ($col === 'pb_books' ? 'title,subtitle,authors,subjects,keywords'
                    : 'name,description'),
                'per_page' => $perPage,
                'page' => $page,
                'highlight_full_fields' => $col === 'pb_sections' ? 'title,content' : 'name,title',
            ];
        }

        try {
            $results = $client->getClient()->multi_search->perform($searchRequests);
        } catch (\Throwable $e) {
            return new WP_REST_Response([
                'code' => 'search_error',
                'message' => $e->getMessage(),
            ], 500);
        }

        $totalFound = 0;
        $formatted = [];
        foreach (($results['results'] ?? []) as $idx => $result) {
            $colName = $targetCollections[$idx] ?? 'unknown';
            $found = $result['found'] ?? 0;
            $totalFound += $found;
            $formatted[str_replace('pb_', '', $colName)] = [
                'hits' => $result['hits'] ?? [],
                'found' => $found,
            ];
        }

        return new WP_REST_Response([
            'results' => $formatted,
            'total_found' => $totalFound,
            'search_time_ms' => $results['search_time_ms'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
        ], 200);
    }
}
