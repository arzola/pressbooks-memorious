<?php

namespace PressbooksBorges\Search;

use PressbooksBorges\Indexing\IndexJobProcessor;
use PressbooksBorges\Indexing\Indexers\BooksIndexer;
use PressbooksBorges\Indexing\Indexers\ContributorsIndexer;
use PressbooksBorges\Indexing\Indexers\SectionsIndexer;

class SearchService
{
    private TypesenseClient $client;

    public function __construct(TypesenseClient $client)
    {
        $this->client = $client;
    }

    public function getClient(): TypesenseClient
    {
        return $this->client;
    }

    public function getIndexers(): array
    {
        return apply_filters('pb_borges_indexers', [
            new SectionsIndexer,
            new BooksIndexer,
            new ContributorsIndexer,
        ]);
    }

    public function enqueueUpsertSection(int $blogId, int $postId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'upsert_section', ['post_id' => $postId]);
    }

    public function enqueueDeleteSection(int $blogId, int $postId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'delete_section', ['post_id' => $postId]);
    }

    public function enqueueUpsertBook(int $blogId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'upsert_book');
    }

    public function enqueueDeleteBook(int $blogId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'delete_book');
    }

    public function enqueueUpsertContributor(int $termId): void
    {
        IndexJobProcessor::enqueueJob(0, 'upsert_contributor', ['term_id' => $termId]);
    }

    public function enqueueDeleteContributor(int $termId): void
    {
        IndexJobProcessor::enqueueJob(0, 'delete_contributor', ['term_id' => $termId]);
    }

    public function enqueueReindexBook(int $blogId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'reindex_book');
    }

    public function ensureCollections(): void
    {
        $collections = Collections::all();
        $existingCollections = array_column(
            $this->client->getClient()->collections->retrieve(),
            'name'
        );

        foreach ($collections as $schema) {
            if (! in_array($schema['name'], $existingCollections, true)) {
                $this->client->getClient()->collections->create($schema);
            }
        }
    }
}
