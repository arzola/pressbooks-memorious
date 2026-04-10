<?php

namespace PressbooksBorges\Search;

class KeyGenerator
{
    public static function generateSearchKey(int $userId, ?int $currentBlogId = null): string
    {
        $settings = get_site_option('pb_borges_settings', []);
        $parentKey = $settings['typesense_search_key'] ?? '';

        $cacheKey = "pb_borges_key_{$userId}_" . md5(json_encode($currentBlogId));
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $filterBy = $currentBlogId !== null
            ? self::buildWebbookFilter($currentBlogId, is_user_logged_in())
            : self::buildAdminFilter(self::getUserBlogIds($userId));

        $scopedKey = self::deriveScopedKey($parentKey, [
            'filter_by' => $filterBy,
            'expires_at' => time() + 3600,
        ]);

        set_transient($cacheKey, $scopedKey, 50 * MINUTE_IN_SECONDS);

        return $scopedKey;
    }

    public static function buildAdminFilter(array $blogIds): string
    {
        $ids = implode(',', $blogIds);

        return "blog_id:=[{$ids}] && post_status:=[publish,private,draft]";
    }

    public static function buildWebbookFilter(int $blogId, bool $isLoggedIn): string
    {
        if ($isLoggedIn) {
            return "blog_id:={$blogId} && post_status:=[publish,web-only]";
        }

        return self::buildAnonymousFilter($blogId);
    }

    public static function buildAnonymousFilter(int $blogId): string
    {
        return "blog_id:={$blogId} && post_status:=publish";
    }

    public static function getUserBlogIds(int $userId): array
    {
        $blogs = get_blogs_of_user($userId);

        return array_map(fn ($blog) => (int) $blog->userblog_id, $blogs);
    }

    private static function deriveScopedKey(string $parentKey, array $parameters): string
    {
        $parameters['expires_at'] = (int) ($parameters['expires_at'] ?? time() + 3600);
        ksort($parameters);

        $base64 = base64_encode(json_encode($parameters));
        $base64 = rtrim($base64, '=');

        $hmac = hash_hmac('sha256', $base64, $parentKey);

        return "{$hmac}{$base64}";
    }
}
