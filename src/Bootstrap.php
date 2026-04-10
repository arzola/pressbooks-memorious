<?php

namespace PressbooksBorges;

use Pressbooks\Container;
use PressbooksBorges\Admin\SearchAdmin;
use PressbooksBorges\Admin\SearchBar;
use PressbooksBorges\Api\SearchEndpoint;
use PressbooksBorges\Indexing\IndexJobProcessor;
use PressbooksBorges\Search\SearchService;
use PressbooksBorges\Search\TypesenseClient;

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
        $this->enqueueScripts();
    }

    private function registerBlade(): void
    {
        Container::get('Blade')->addNamespace(
            'PressbooksBorges',
            dirname(__DIR__) . '/resources/views'
        );
    }

    private function registerServices(): void
    {
        Container::set('Borges\Search', function () {
            return new SearchService(new TypesenseClient(
                nodes: [],
                adminApiKey: ''
            ));
        }, 'singleton');
    }

    private function registerActions(): void
    {
        $settings = get_site_option('pb_borges_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return;
        }

        IndexJobProcessor::register();
        SearchEndpoint::register();

        $this->registerIndexingHooks();
    }

    private function registerIndexingHooks(): void
    {
        $settings = get_site_option('pb_borges_settings', []);
        $search = fn () => Container::get('Borges\Search');

        $indexedPostTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];

        add_action('save_post', function (int $postId, \WP_Post $post) use ($search, $indexedPostTypes, $settings) {
            if (! in_array($post->post_type, $indexedPostTypes, true)) {
                return;
            }
            if ($post->post_status === 'draft' && empty($settings['index_draft_content'])) {
                return;
            }
            $blogId = get_current_blog_id();
            $search()->enqueueUpsertSection($blogId, $postId);
        }, 10, 2);

        add_action('delete_post', function (int $postId) use ($search) {
            $post = get_post($postId);
            if (! $post) {
                return;
            }
            $indexedPostTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];
            if (in_array($post->post_type, $indexedPostTypes, true)) {
                $search()->enqueueDeleteSection(get_current_blog_id(), $postId);
            }
        });

        add_action('wp_initialize_site', function (\WP_Site $site) use ($search) {
            $search()->enqueueReindexBook($site->blog_id);
        });

        add_action('wp_update_site', function (\WP_Site $newSite) use ($search) {
            $search()->enqueueUpsertBook($newSite->blog_id);
        });

        add_action('wp_delete_site', function (\WP_Site $oldSite) use ($search) {
            $search()->enqueueDeleteBook($oldSite->blog_id);
        });

        add_action('edited_term', function (int $termId, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $search()->enqueueUpsertContributor($termId);
            }
        }, 10, 3);

        add_action('created_term', function (int $termId, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $search()->enqueueUpsertContributor($termId);
            }
        }, 10, 3);

        add_action('delete_term', function (int $term, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $search()->enqueueDeleteContributor($term);
            }
        }, 10, 3);
    }

    private function registerMenus(): void
    {
        SearchAdmin::init();
    }

    private function enqueueScripts(): void
    {
        SearchBar::init();
        SearchBar::enqueueAssets();
    }
}
