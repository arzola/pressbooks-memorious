<?php

namespace PressbooksBorges\Indexing\Indexers;

use PressbooksBorges\Indexing\IndexerInterface;

class ContributorsIndexer implements IndexerInterface
{
    public function getCollectionName(): string
    {
        return 'pb_contributors';
    }

    public function getPostTypes(): array
    {
        return [];
    }

    public function transformDocument(int $blogId, int $postId = 0, ?int $termId = null): ?array
    {
        if ($termId === null) {
            return null;
        }

        $term = get_term($termId, 'contributor');

        if (! $term || is_wp_error($term)) {
            return null;
        }

        $contributorTypes = [];
        $termMeta = get_term_meta($term->term_id);
        foreach ($termMeta as $key => $values) {
            if (str_starts_with($key, 'pb_contributor_') && ! empty($values[0])) {
                $contributorTypes[] = str_replace('pb_contributor_', '', $key);
            }
        }

        $document = [
            'id' => "contributor_{$term->term_id}",
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'contributor_type' => $contributorTypes,
            'description' => $term->description ?: null,
            'blog_ids' => [],
            'book_count' => 0,
            'section_count' => 0,
        ];

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId = 0, ?int $termId = null): ?string
    {
        if ($termId === null) {
            return null;
        }

        return "contributor_{$termId}";
    }
}
