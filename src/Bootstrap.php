<?php

namespace PressbooksBeacon;

use Pressbooks\Container;
use PressbooksBeacon\Admin\SearchAdmin;
use PressbooksBeacon\Admin\SearchBar;
use PressbooksBeacon\Api\SearchEndpoint;
use PressbooksBeacon\Indexing\IndexJobProcessor;
use PressbooksBeacon\Search\SearchService;
use PressbooksBeacon\Cli\BeaconCommand;
use PressbooksBeacon\Search\TypesenseClient;

final class Bootstrap
{
    private static ?Bootstrap $instance = null;

    public static function run(): void
    {
        if (! self::$instance) {
            self::$instance = new self;
            self::$instance->setUp();
        }
    }

    public function setUp(): void
    {
        $this->registerBlade();
        $this->registerServices();
        $this->registerActions();
        $this->registerMenus();
        $this->registerCli();
        $this->enqueueScripts();
    }

    private function registerBlade(): void
    {
        Container::get('Blade')->addNamespace(
            'PressbooksBeacon',
            dirname(__DIR__) . '/resources/views'
        );
    }

    private function registerServices(): void
    {
        Container::set('Beacon\Search', function () {
            $settings = get_site_option('pb_beacon_settings', []);

            if (empty($settings['typesense_nodes'])) {
                return;
            }

            return new SearchService(TypesenseClient::fromSettings());
        }, 'singleton');
    }

    private function registerActions(): void
    {
        $settings = get_site_option('pb_beacon_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return;
        }

        IndexJobProcessor::register();
        SearchEndpoint::register();

        $this->registerIndexingHooks();
    }

    private function registerIndexingHooks(): void
    {
        $settings = get_site_option('pb_beacon_settings', []);
        $search = fn () => Container::get('Beacon\Search');

        $indexedPostTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];

        add_action('save_post', function (int $postId, \WP_Post $post) use ($search, $indexedPostTypes, $settings) {
            if (! in_array($post->post_type, $indexedPostTypes, true)) {
                return;
            }
            if ($post->post_status === 'draft' && empty($settings['index_draft_content'])) {
                return;
            }
            $svc = $search();
            if (! $svc) {
                return;
            }
            $blogId = get_current_blog_id();
            $svc->enqueueUpsertSection($blogId, $postId);
        }, 10, 2);

        add_action('delete_post', function (int $postId) use ($search) {
            $post = get_post($postId);
            if (! $post) {
                return;
            }
            $svc = $search();
            if (! $svc) {
                return;
            }
            $indexedPostTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];
            if (in_array($post->post_type, $indexedPostTypes, true)) {
                $svc->enqueueDeleteSection(get_current_blog_id(), $postId);
            }
        });

        add_action('wp_initialize_site', function (\WP_Site $site) use ($search) {
            $svc = $search();
            if ($svc) {
                $svc->enqueueReindexBook($site->blog_id);
            }
        });

        add_action('wp_update_site', function (\WP_Site $newSite) use ($search) {
            $svc = $search();
            if ($svc) {
                $svc->enqueueUpsertBook($newSite->blog_id);
            }
        });

        add_action('wp_delete_site', function (\WP_Site $oldSite) use ($search) {
            $svc = $search();
            if ($svc) {
                $svc->enqueueDeleteBook($oldSite->blog_id);
            }
        });

        add_action('edited_term', function (int $termId, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $svc = $search();
                if ($svc) {
                    $svc->enqueueUpsertContributor($termId);
                }
            }
        }, 10, 3);

        add_action('created_term', function (int $termId, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $svc = $search();
                if ($svc) {
                    $svc->enqueueUpsertContributor($termId);
                }
            }
        }, 10, 3);

        add_action('delete_term', function (int $term, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $svc = $search();
                if ($svc) {
                    $svc->enqueueDeleteContributor($term);
                }
            }
        }, 10, 3);
    }

    private function registerMenus(): void
    {
        SearchAdmin::init();
    }

    private function registerCli(): void
    {
        if (! defined('WP_CLI') || ! WP_CLI) {
            return;
        }

        $cmd = BeaconCommand::class;

        \WP_CLI::add_command('beacon reindex', [$cmd, 'reindex'], [
            'shortdesc' => 'Queue reindex for one or all books',
            'synopsis' => '[<blog_id>]',
        ]);

        \WP_CLI::add_command('beacon process-jobs', [$cmd, 'processJobs'], [
            'shortdesc' => 'Process pending indexing jobs',
            'synopsis' => '[<limit>]',
        ]);

        \WP_CLI::add_command('beacon create-collections', [$cmd, 'createCollections'], [
            'shortdesc' => 'Create Typesense collections',
        ]);

        \WP_CLI::add_command('beacon reset-collections', [$cmd, 'resetCollections'], [
            'shortdesc' => 'Delete and recreate Typesense collections',
        ]);

        \WP_CLI::add_command('beacon reindex-reset', [$cmd, 'reindexReset'], [
            'shortdesc' => 'Reset collections, clear queue, and reindex everything',
            'synopsis' => '[<blog_id>]',
        ]);

        \WP_CLI::add_command('beacon job-status', [$cmd, 'jobStatus'], [
            'shortdesc' => 'Show indexing job queue status',
        ]);
    }

    private function enqueueScripts(): void
    {
        SearchBar::init();
        SearchBar::enqueueAssets();
    }
}
