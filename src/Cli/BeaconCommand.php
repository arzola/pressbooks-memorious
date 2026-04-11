<?php

namespace PressbooksBeacon\Cli;

use PressbooksBeacon\Indexing\IndexJobProcessor;
use PressbooksBeacon\Search\Collections;
use PressbooksBeacon\Search\SearchService;
use PressbooksBeacon\Search\TypesenseClient;
use WP_CLI;

class BeaconCommand
{
    public function reindex(array $args): void
    {
        $blogId = $args[0] ?? null;

        $search = new SearchService(TypesenseClient::fromSettings());

        if ($blogId) {
            WP_CLI::log("Queuing reindex for blog {$blogId}...");
            $search->enqueueReindexBook((int) $blogId);
            WP_CLI::success("Reindex job queued for blog {$blogId}.");
            return;
        }

        $sites = get_sites(['number' => 0]);
        $count = count($sites);

        WP_CLI::log("Queuing reindex for {$count} books...");

        foreach ($sites as $site) {
            $search->enqueueReindexBook((int) $site->blog_id);
        }

        WP_CLI::success("Queued {$count} books for reindexing.");
    }

    public function processJobs(array $args): void
    {
        $limit = (int) ($args[0] ?? 50);

        WP_CLI::log("Processing up to {$limit} pending jobs...");

        $pending = IndexJobProcessor::getPendingJobs($limit);

        if (empty($pending)) {
            WP_CLI::success('No pending jobs found.');
            return;
        }

        foreach ($pending as $job) {
            IndexJobProcessor::markProcessing($job->id);
            try {
                IndexJobProcessor::processJob($job);
                IndexJobProcessor::markCompleted($job->id);
                WP_CLI::log("[ok] Job {$job->id}: {$job->job_type} (blog {$job->blog_id})");
            } catch (\Throwable $e) {
                IndexJobProcessor::markFailed($job->id, $e->getMessage());
                WP_CLI::warning("[fail] Job {$job->id}: {$job->job_type} - {$e->getMessage()}");
            }
        }

        WP_CLI::success('Done processing jobs.');
    }

    public function createCollections(array $args): void
    {
        WP_CLI::log('Creating Typesense collections...');

        $search = new SearchService(TypesenseClient::fromSettings());
        $search->ensureCollections();

        WP_CLI::success('Collections created successfully.');
    }

    public function resetCollections(array $args): void
    {
        self::doResetCollections();

        $search = new SearchService(TypesenseClient::fromSettings());
        $search->ensureCollections();

        WP_CLI::success('Collections reset successfully.');
    }

    public function reindexReset(array $args): void
    {
        WP_CLI::log('Resetting collections and queuing full reindex...');

        self::doResetCollections();

        $search = new SearchService(TypesenseClient::fromSettings());
        $search->ensureCollections();

        app('db')->table('pressbooks_beacon_index_jobs')->truncate();

        $this->reindex($args);
    }

    public function jobStatus(array $args): void
    {
        $db = app('db')->table('pressbooks_beacon_index_jobs');

        $pending = $db->where('status', 'pending')->count();
        $processing = $db->where('status', 'processing')->count();
        $completed = $db->where('status', 'completed')->count();
        $failed = $db->where('status', 'failed')->count();

        WP_CLI::log("Pending:    {$pending}");
        WP_CLI::log("Processing: {$processing}");
        WP_CLI::log("Completed:  {$completed}");

        if ($failed > 0) {
            WP_CLI::warning("Failed:     {$failed}");

            $failedJobs = $db->where('status', 'failed')->limit(10)->get();
            foreach ($failedJobs as $job) {
                WP_CLI::warning("  Job {$job->id}: {$job->job_type} (blog {$job->blog_id}) - {$job->error_message}");
            }
        }
    }

    public static function doResetCollections(): void
    {
        $client = TypesenseClient::fromSettings()->getClient();

        foreach (array_keys(Collections::all()) as $name) {
            try {
                $client->collections[$name]->delete();
                WP_CLI::log("Deleted collection: {$name}");
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), '404')) {
                    WP_CLI::log("Collection does not exist, skipping: {$name}");
                } else {
                    WP_CLI::warning("Failed to delete {$name}: {$e->getMessage()}");
                }
            }
        }
    }
}
