<?php

namespace PressbooksBorges\Indexing\Indexers;

use PressbooksBorges\Indexing\IndexerInterface;

class BooksIndexer implements IndexerInterface
{
    public function getCollectionName(): string
    {
        return 'pb_books';
    }

    public function getPostTypes(): array
    {
        return [];
    }

    public function transformDocument(int $blogId, int $postId = 0): ?array
    {
        $switched = false;
        if (get_current_blog_id() !== $blogId) {
            switch_to_blog($blogId);
            $switched = true;
        }

        $bookInfo = \Pressbooks\Book::getBookInformation($blogId);

        if (! $bookInfo) {
            if ($switched) {
                restore_current_blog();
            }
            return null;
        }

        $authors = [];
        if (! empty($bookInfo['pb_author'])) {
            $authors = array_map('trim', explode(',', $bookInfo['pb_author']));
        }

        $subjects = [];
        if (! empty($bookInfo['pb_subject'])) {
            $subjects = array_map('trim', explode(',', $bookInfo['pb_subject']));
        }

        $keywords = [];
        if (! empty($bookInfo['pb_keywords_tags'])) {
            $keywords = array_map('trim', explode(',', $bookInfo['pb_keywords_tags']));
        }

        $isPublic = (bool) get_blog_details($blogId)->public;
        $blogMeta = get_site_meta($blogId);
        $inDirectory = ! empty($blogMeta['pb_in_catalog']) && (bool) $blogMeta['pb_in_catalog'][0];

        $document = [
            'id' => "book_{$blogId}",
            'blog_id' => $blogId,
            'title' => $bookInfo['pb_title'] ?? '',
            'subtitle' => $bookInfo['pb_subtitle'] ?? null,
            'authors' => $authors,
            'language' => $bookInfo['pb_language'] ?? null,
            'license' => $bookInfo['pb_book_license'] ?? null,
            'subjects' => $subjects,
            'keywords' => $keywords,
            'word_count' => (int) ($blogMeta['pb_word_count'][0] ?? 0),
            'cover_url' => $bookInfo['pb_cover_image'] ?? null,
            'book_url' => get_blogaddress_by_id($blogId),
            'is_public' => $isPublic,
            'in_directory' => $inDirectory,
            'updated_at' => time(),
        ];

        if ($switched) {
            restore_current_blog();
        }

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId = 0): ?string
    {
        return "book_{$blogId}";
    }
}
