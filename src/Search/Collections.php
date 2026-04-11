<?php

namespace PressbooksBeacon\Search;

class Collections
{
    public static function sections(): array
    {
        return [
            'name' => 'pb_sections',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'blog_id', 'type' => 'int64', 'facet' => true, 'sort' => true],
                ['name' => 'post_id', 'type' => 'int64'],
                ['name' => 'post_type', 'type' => 'string', 'facet' => true],
                ['name' => 'post_status', 'type' => 'string', 'facet' => true],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'short_title', 'type' => 'string', 'optional' => true],
                ['name' => 'content', 'type' => 'string'],
                ['name' => 'authors', 'type' => 'string[]', 'facet' => true],
                ['name' => 'section_license', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'language', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'parent_id', 'type' => 'int64', 'optional' => true],
                ['name' => 'menu_order', 'type' => 'int32', 'optional' => true],
                ['name' => 'book_title', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'book_url', 'type' => 'string', 'optional' => true],
                ['name' => 'edit_url', 'type' => 'string', 'optional' => true],
                ['name' => 'view_url', 'type' => 'string', 'optional' => true],
                ['name' => 'updated_at', 'type' => 'int64', 'sort' => true],
            ],
            'default_sorting_field' => 'updated_at',
        ];
    }

    public static function books(): array
    {
        return [
            'name' => 'pb_books',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'blog_id', 'type' => 'int64', 'facet' => true, 'sort' => true],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'subtitle', 'type' => 'string', 'optional' => true],
                ['name' => 'authors', 'type' => 'string[]', 'facet' => true],
                ['name' => 'language', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'license', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'subjects', 'type' => 'string[]', 'facet' => true, 'optional' => true],
                ['name' => 'keywords', 'type' => 'string[]', 'optional' => true],
                ['name' => 'word_count', 'type' => 'int64', 'optional' => true],
                ['name' => 'cover_url', 'type' => 'string', 'optional' => true],
                ['name' => 'book_url', 'type' => 'string', 'optional' => true],
                ['name' => 'is_public', 'type' => 'bool', 'facet' => true],
                ['name' => 'in_directory', 'type' => 'bool', 'facet' => true, 'optional' => true],
                ['name' => 'updated_at', 'type' => 'int64', 'sort' => true],
            ],
            'default_sorting_field' => 'updated_at',
        ];
    }

    public static function contributors(): array
    {
        return [
            'name' => 'pb_contributors',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'term_id', 'type' => 'int64'],
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'slug', 'type' => 'string'],
                ['name' => 'contributor_type', 'type' => 'string[]', 'facet' => true],
                ['name' => 'description', 'type' => 'string', 'optional' => true],
                ['name' => 'blog_ids', 'type' => 'int64[]', 'facet' => true],
                ['name' => 'book_count', 'type' => 'int32', 'sort' => true],
                ['name' => 'section_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'profile_url', 'type' => 'string', 'optional' => true],
            ],
            'default_sorting_field' => 'book_count',
        ];
    }

    public static function all(): array
    {
        $core = [
            'pb_sections' => self::sections(),
            'pb_books' => self::books(),
            'pb_contributors' => self::contributors(),
        ];

        return apply_filters('pb_beacon_collections', $core);
    }
}
