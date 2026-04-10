<?php

namespace PressbooksBorges\Indexing;

interface IndexerInterface
{
    public function getCollectionName(): string;

    public function getPostTypes(): array;

    public function transformDocument(int $blogId, int $postId): ?array;

    public function deleteDocument(int $blogId, int $postId): ?string;
}
