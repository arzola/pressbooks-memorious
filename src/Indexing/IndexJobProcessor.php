<?php

namespace PressbooksBorges\Indexing;

class IndexJobProcessor
{
    public static function register(): void
    {
        add_action('pb_borges_index_processor', [self::class, 'processQueue']);

        if (! wp_next_scheduled('pb_borges_index_processor')) {
            wp_schedule_event(time(), 'every_minute', 'pb_borges_index_processor');
        }
    }

    public static function enqueueJob(int $blogId, string $jobType, array $payload = []): void
    {
        $payloadJson = ! empty($payload) ? wp_json_encode($payload) : null;

        $existing = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', $blogId)
            ->where('job_type', $jobType)
            ->where('status', 'pending')
            ->when($payloadJson, fn ($q) => $q->where('payload', $payloadJson))
            ->first();

        if ($existing) {
            app('db')->table('pressbooks_borges_index_jobs')
                ->where('id', $existing->id)
                ->update(['updated_at' => current_time('mysql', true)]);
            return;
        }

        app('db')->table('pressbooks_borges_index_jobs')->insert([
            'blog_id' => $blogId,
            'job_type' => $jobType,
            'payload' => $payloadJson,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
        ]);
    }

    public static function getPendingJobs(int $limit = 50): array
    {
        return app('db')->table('pressbooks_borges_index_jobs')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public static function markProcessing(int $jobId): void
    {
        app('db')->table('pressbooks_borges_index_jobs')
            ->where('id', $jobId)
            ->update([
                'status' => 'processing',
                'updated_at' => current_time('mysql', true),
            ]);
    }

    public static function markCompleted(int $jobId): void
    {
        app('db')->table('pressbooks_borges_index_jobs')
            ->where('id', $jobId)
            ->update([
                'status' => 'completed',
                'processed_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ]);
    }

    public static function markFailed(int $jobId, string $errorMessage): void
    {
        $job = app('db')->table('pressbooks_borges_index_jobs')->find($jobId);
        if (! $job) {
            return;
        }

        $settings = get_site_option('pb_borges_settings', []);
        $maxRetries = $settings['max_retries'] ?? 3;
        $newAttempts = $job->attempts + 1;

        if ($newAttempts < $maxRetries) {
            app('db')->table('pressbooks_borges_index_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'pending',
                    'attempts' => $newAttempts,
                    'error_message' => $errorMessage,
                    'updated_at' => current_time('mysql', true),
                ]);
        } else {
            app('db')->table('pressbooks_borges_index_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'failed',
                    'attempts' => $newAttempts,
                    'error_message' => $errorMessage,
                    'processed_at' => current_time('mysql', true),
                    'updated_at' => current_time('mysql', true),
                ]);

            do_action('pb_borges_job_failed', $jobId, $errorMessage);
        }
    }

    public static function processQueue(): void
    {
        $settings = get_site_option('pb_borges_settings', []);
        $batchSize = $settings['batch_size'] ?? 50;

        $jobs = self::getPendingJobs($batchSize);

        foreach ($jobs as $job) {
            self::markProcessing($job->id);
            try {
                self::processJob($job);
                self::markCompleted($job->id);
            } catch (\Throwable $e) {
                self::markFailed($job->id, $e->getMessage());
            }
        }
    }

    private static function processJob(object $job): void
    {
        $client = \PressbooksBorges\Search\TypesenseClient::fromSettings();
        $payload = $job->payload ? json_decode($job->payload, true) : [];

        $indexers = apply_filters('pb_borges_indexers', [
            new Indexers\SectionsIndexer,
            new Indexers\BooksIndexer,
            new Indexers\ContributorsIndexer,
        ]);

        switch ($job->job_type) {
            case 'upsert_section':
                foreach ($indexers as $indexer) {
                    if (in_array('chapter', $indexer->getPostTypes(), true) ||
                        in_array('front-matter', $indexer->getPostTypes(), true)
                    ) {
                        $doc = $indexer->transformDocument($job->blog_id, $payload['post_id'] ?? 0);
                        if ($doc) {
                            $client->getClient()->collections[$indexer->getCollectionName()]->documents->upsert($doc);
                        }
                    }
                }
                break;

            case 'delete_section':
                foreach ($indexers as $indexer) {
                    $docId = $indexer->deleteDocument($job->blog_id, $payload['post_id'] ?? 0);
                    if ($docId) {
                        try {
                            $client->getClient()->collections[$indexer->getCollectionName()]->documents[$docId]->delete();
                        } catch (\Throwable $e) {
                            if (! str_contains($e->getMessage(), '404')) {
                                throw $e;
                            }
                        }
                    }
                }
                break;

            case 'upsert_book':
                foreach ($indexers as $indexer) {
                    if ($indexer->getCollectionName() === 'pb_books') {
                        $doc = $indexer->transformDocument($job->blog_id);
                        if ($doc) {
                            $client->getClient()->collections['pb_books']->documents->upsert($doc);
                        }
                    }
                }
                break;

            case 'delete_book':
                try {
                    $client->getClient()->collections['pb_books']->documents["book_{$job->blog_id}"]->delete();
                } catch (\Throwable $e) {
                    if (! str_contains($e->getMessage(), '404')) {
                        throw $e;
                    }
                }
                try {
                    $client->getClient()->collections['pb_sections']->documents->delete(['filter_by' => "blog_id:{$job->blog_id}"]);
                } catch (\Throwable $e) {
                    if (! str_contains($e->getMessage(), '404')) {
                        throw $e;
                    }
                }
                break;

            case 'upsert_contributor':
                foreach ($indexers as $indexer) {
                    if ($indexer->getCollectionName() === 'pb_contributors') {
                        $doc = $indexer->transformDocument(0, 0, $payload['term_id'] ?? null);
                        if ($doc) {
                            $client->getClient()->collections['pb_contributors']->documents->upsert($doc);
                        }
                    }
                }
                break;

            case 'delete_contributor':
                $termId = $payload['term_id'] ?? null;
                if ($termId) {
                    try {
                        $client->getClient()->collections['pb_contributors']->documents["contributor_{$termId}"]->delete();
                    } catch (\Throwable $e) {
                        if (! str_contains($e->getMessage(), '404')) {
                            throw $e;
                        }
                    }
                }
                break;

            case 'reindex_book':
                do_action('pb_borges_reindex_started', $job->blog_id);
                try {
                    $client->getClient()->collections['pb_sections']->documents->delete(['filter_by' => "blog_id:{$job->blog_id}"]);
                } catch (\Throwable $e) {
                    // Ignore 404s
                }
                $switched = false;
                if (get_current_blog_id() !== $job->blog_id) {
                    switch_to_blog($job->blog_id);
                    $switched = true;
                }
                $postTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];
                $docs = [];
                foreach ($postTypes as $pt) {
                    $posts = get_posts(['post_type' => $pt, 'posts_per_page' => -1, 'post_status' => 'any']);
                    foreach ($posts as $p) {
                        foreach ($indexers as $indexer) {
                            if (in_array($pt, $indexer->getPostTypes(), true)) {
                                $doc = $indexer->transformDocument($job->blog_id, $p->ID);
                                if ($doc) {
                                    $docs[] = $doc;
                                }
                            }
                        }
                    }
                }
                if (! empty($docs)) {
                    $client->getClient()->collections['pb_sections']->documents->import($docs, ['action' => 'upsert']);
                }
                foreach ($indexers as $indexer) {
                    if ($indexer->getCollectionName() === 'pb_books') {
                        $doc = $indexer->transformDocument($job->blog_id);
                        if ($doc) {
                            $client->getClient()->collections['pb_books']->documents->upsert($doc);
                        }
                    }
                }
                if ($switched) {
                    restore_current_blog();
                }
                do_action('pb_borges_reindex_completed', $job->blog_id);
                break;
        }
    }
}
