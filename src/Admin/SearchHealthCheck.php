<?php

namespace PressbooksMemorious\Admin;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;
use PressbooksMemorious\Search\TypesenseClient;

class SearchHealthCheck extends Check
{
    public function run(): Result
    {
        $settings = get_site_option('pb_memorious_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return Result::failed('Typesense is not configured.');
        }

        try {
            $client = TypesenseClient::fromSettings();
            $client->getClient()->health->retrieve();

            $pendingJobs = app('db')->table('pressbooks_memorious_index_jobs')
                ->where('status', 'pending')
                ->count();

            if ($pendingJobs > 1000) {
                return Result::failed("Typesense is healthy but {$pendingJobs} pending indexing jobs are backlogged.");
            }

            return $pendingJobs > 100
                ? Result::ok("Healthy. {$pendingJobs} pending jobs in queue.")->withData(['pending_jobs' => $pendingJobs])
                : Result::ok('Healthy.')->withData(['pending_jobs' => $pendingJobs]);
        } catch (\Throwable $e) {
            return Result::failed('Typesense connection failed: ' . $e->getMessage());
        }
    }
}
