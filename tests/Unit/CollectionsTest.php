<?php

namespace Tests\Unit;

use PressbooksBeacon\Search\Collections;
use Tests\TestCase;

class CollectionsTest extends TestCase
{
    public function test_sections_returns_valid_schema(): void
    {
        $schema = Collections::sections();

        $this->assertEquals('pb_sections', $schema['name']);
        $this->assertEquals('updated_at', $schema['default_sorting_field']);
        $this->assertArrayHasKey('fields', $schema);

        $fieldNames = array_column($schema['fields'], 'name');
        $this->assertContains('id', $fieldNames);
        $this->assertContains('blog_id', $fieldNames);
        $this->assertContains('post_id', $fieldNames);
        $this->assertContains('post_type', $fieldNames);
        $this->assertContains('title', $fieldNames);
        $this->assertContains('content', $fieldNames);
        $this->assertContains('authors', $fieldNames);
        $this->assertContains('book_title', $fieldNames);
    }

    public function test_books_returns_valid_schema(): void
    {
        $schema = Collections::books();

        $this->assertEquals('pb_books', $schema['name']);
        $this->assertEquals('updated_at', $schema['default_sorting_field']);

        $fieldNames = array_column($schema['fields'], 'name');
        $this->assertContains('id', $fieldNames);
        $this->assertContains('blog_id', $fieldNames);
        $this->assertContains('title', $fieldNames);
        $this->assertContains('authors', $fieldNames);
    }

    public function test_contributors_returns_valid_schema(): void
    {
        $schema = Collections::contributors();

        $this->assertEquals('pb_contributors', $schema['name']);
        $this->assertEquals('book_count', $schema['default_sorting_field']);

        $fieldNames = array_column($schema['fields'], 'name');
        $this->assertContains('id', $fieldNames);
        $this->assertContains('name', $fieldNames);
        $this->assertContains('slug', $fieldNames);
        $this->assertContains('contributor_type', $fieldNames);
        $this->assertContains('blog_ids', $fieldNames);
    }

    public function test_all_returns_three_collections(): void
    {
        $all = Collections::all();

        $this->assertCount(3, $all);
        $this->assertArrayHasKey('pb_sections', $all);
        $this->assertArrayHasKey('pb_books', $all);
        $this->assertArrayHasKey('pb_contributors', $all);
    }

    public function test_all_can_be_filtered(): void
    {
        add_filter('pb_beacon_collections', function (array $collections) {
            $collections['pb_custom'] = [
                'name' => 'pb_custom',
                'fields' => [['name' => 'id', 'type' => 'string']],
                'default_sorting_field' => 'id',
            ];

            return $collections;
        });

        $all = Collections::all();

        $this->assertArrayHasKey('pb_custom', $all);
        $this->assertCount(4, $all);
    }

    public function test_faceted_fields_have_facet_flag(): void
    {
        $sections = Collections::sections();
        $faceted = array_filter($sections['fields'], fn ($f) => ! empty($f['facet']));
        $facetedNames = array_column($faceted, 'name');

        $this->assertContains('blog_id', $facetedNames);
        $this->assertContains('post_type', $facetedNames);
        $this->assertContains('authors', $facetedNames);
        $this->assertContains('book_title', $facetedNames);
    }

    public function test_optional_fields_are_marked(): void
    {
        $sections = Collections::sections();
        $optional = array_filter($sections['fields'], fn ($f) => ! empty($f['optional']));
        $optionalNames = array_column($optional, 'name');

        $this->assertContains('short_title', $optionalNames);
        $this->assertContains('section_license', $optionalNames);
        $this->assertContains('language', $optionalNames);
        $this->assertContains('edit_url', $optionalNames);
        $this->assertContains('view_url', $optionalNames);
    }
}
