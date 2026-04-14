<?php

namespace PressbooksMemorious\Search;

class KeyGenerator
{
    public static function generateSearchKey(int $userId, ?int $currentBlogId = null): string
    {
        $settings = get_site_option('pb_memorious_settings', []);
        $parentKey = $settings['typesense_search_key'] ?? '';

        $blogIds = self::getUserBlogIds($userId);
        $cacheKey = "pb_memorious_key_{$userId}_" . md5(json_encode($blogIds));
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $filterBy = self::buildAdminFilter($blogIds);

        $parameters = [];
        if ($filterBy) {
            $parameters['filter_by'] = $filterBy;
        }
        $parameters['expires_at'] = time() + 3600;

        $scopedKey = self::deriveScopedKey($parentKey, $parameters);

        set_transient($cacheKey, $scopedKey, 50 * MINUTE_IN_SECONDS);

        return $scopedKey;
    }

    public static function buildAdminFilter(array $blogIds): string
    {
        $ids = implode(',', $blogIds);

        return "blog_id:=[{$ids}]";
    }

    public static function getUserBlogIds(int $userId): array
    {
        $blogs = get_blogs_of_user($userId);

        return array_map(fn ($blog) => (int) $blog->userblog_id, $blogs);
    }

    private static function deriveScopedKey(string $parentKey, array $parameters): string
    {
        $paramStr = json_encode($parameters, JSON_THROW_ON_ERROR);

        $digest = base64_encode(
            hash_hmac('sha256', $paramStr, $parentKey, true)
        );

        $keyPrefix = substr($parentKey, 0, 4);

        $raw = $digest . $keyPrefix . $paramStr;

        return base64_encode($raw);
    }
}
