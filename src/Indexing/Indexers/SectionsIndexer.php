<?php

namespace PressbooksBeacon\Indexing\Indexers;

use PressbooksBeacon\Indexing\IndexerInterface;

class SectionsIndexer implements IndexerInterface
{
    public function getCollectionName(): string
    {
        return 'pb_sections';
    }

    public function getPostTypes(): array
    {
        return ['chapter', 'front-matter', 'back-matter', 'glossary'];
    }

    public function transformDocument(int $blogId, int $postId = 0, ?int $termId = null): ?array
    {
        $switched = false;
        if (get_current_blog_id() !== $blogId) {
            switch_to_blog($blogId);
            $switched = true;
        }

        $post = get_post($postId);

        if (! $post || ! in_array($post->post_type, $this->getPostTypes(), true)) {
            if ($switched) {
                restore_current_blog();
            }
            return null;
        }

        $bookTitle = '';
        $bookUrl = '';
        $language = '';

        $bookInfo = \Pressbooks\Book::getBookInformation($blogId);
        if ($bookInfo) {
            $bookTitle = $bookInfo['pb_title'] ?? '';
            $language = $bookInfo['pb_language'] ?? '';
        }
        $bookUrl = get_blogaddress_by_id($blogId);

        $authors = [];
        $pbAuthors = get_post_meta($postId, 'pb_authors', true);
        if ($pbAuthors) {
            $authors = array_map('trim', explode(',', $pbAuthors));
        }

        $terms = get_the_terms($postId, 'contributor');
        if ($terms && ! is_wp_error($terms)) {
            foreach ($terms as $term) {
                if (! in_array($term->name, $authors, true)) {
                    $authors[] = $term->name;
                }
            }
        }

        $content = wp_strip_all_tags($post->post_content);
        $content = preg_replace('/\s+/', ' ', $content);

        $editUrl = admin_url("post.php?post={$postId}&action=edit");
        $viewUrl = get_permalink($postId);

        $document = [
            'id' => "{$blogId}_{$postId}",
            'blog_id' => $blogId,
            'post_id' => $postId,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
            'title' => $post->post_title,
            'short_title' => get_post_meta($postId, 'pb_short_title', true) ?: null,
            'content' => $content,
            'authors' => $authors,
            'section_license' => get_post_meta($postId, 'pb_section_license', true) ?: null,
            'language' => $language ?: null,
            'parent_id' => $post->post_parent ?: null,
            'menu_order' => $post->menu_order,
            'book_title' => $bookTitle,
            'book_url' => $bookUrl,
            'edit_url' => $editUrl ?: null,
            'view_url' => $viewUrl ?: null,
            'updated_at' => strtotime($post->post_modified_gmt) ?: time(),
        ];

        if ($switched) {
            restore_current_blog();
        }

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId = 0, ?int $termId = null): ?string
    {
        return "{$blogId}_{$postId}";
    }
}
