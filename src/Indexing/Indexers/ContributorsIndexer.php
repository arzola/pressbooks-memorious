<?php

namespace PressbooksMemorious\Indexing\Indexers;

use PressbooksMemorious\Indexing\IndexerInterface;

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

        $contributorTypes = $this->detectContributorTypes($term->term_id);

        $document = [
            'id' => "contributor_{$blogId}_{$term->term_id}",
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'contributor_type' => $contributorTypes,
            'description' => $term->description ?: null,
            'blog_ids' => [$blogId],
            'book_count' => 1,
            'section_count' => 0,
        ];

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId = 0, ?int $termId = null): ?string
    {
        if ($termId === null) {
            return null;
        }

        return "contributor_{$blogId}_{$termId}";
    }

    public function getContributorsForBlog(int $blogId): array
    {
        $switched = false;
        if (get_current_blog_id() !== $blogId) {
            switch_to_blog($blogId);
            $switched = true;
        }

        $terms = get_terms([
            'taxonomy' => 'contributor',
            'hide_empty' => false,
            'number' => 0,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            if ($switched) {
                restore_current_blog();
            }
            return [];
        }

        $docs = [];
        foreach ($terms as $term) {
            $contributorTypes = $this->detectContributorTypes($term->term_id);
            $linkedPosts = $this->getLinkedPostCount($term->term_id);

            $docs[] = [
                'id' => "contributor_{$blogId}_{$term->term_id}",
                'term_id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'contributor_type' => $contributorTypes,
                'description' => $term->description ?: null,
                'blog_ids' => [$blogId],
                'book_count' => $linkedPosts > 0 ? 1 : 0,
                'section_count' => $linkedPosts,
            ];
        }

        if ($switched) {
            restore_current_blog();
        }

        return $docs;
    }

    private function detectContributorTypes(int $termId): array
    {
        $types = [];
        $typeMetaKeys = [
            'pb_authors' => 'author',
            'pb_editors' => 'editor',
            'pb_contributors' => 'contributor',
            'pb_translators' => 'translator',
            'pb_reviewers' => 'reviewer',
            'pb_illustrators' => 'illustrator',
        ];

        $slug = get_term($termId, 'contributor')->slug ?? '';
        if (empty($slug)) {
            return $types;
        }

        foreach ($typeMetaKeys as $metaKey => $label) {
            $existing = get_posts([
                'post_type' => ['chapter', 'front-matter', 'back-matter', 'metadata'],
                'posts_per_page' => 1,
                'meta_key' => $metaKey,
                'meta_value' => $slug,
                'fields' => 'ids',
            ]);
            if (! empty($existing)) {
                $types[] = $label;
            }
        }

        return $types;
    }

    private function getLinkedPostCount(int $termId): int
    {
        $postTypes = ['chapter', 'front-matter', 'back-matter', 'glossary', 'metadata', 'part'];

        $count = 0;
        foreach ($postTypes as $pt) {
            $posts = get_posts([
                'post_type' => $pt,
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'contributor',
                        'field' => 'term_id',
                        'terms' => $termId,
                    ],
                ],
                'fields' => 'ids',
            ]);
            $count += count($posts);
        }

        return $count;
    }
}
