<?php

namespace Tests\Unit;

use PressbooksMemorious\Search\KeyGenerator;
use Tests\TestCase;

class KeyGeneratorTest extends TestCase
{
    public function test_derive_scoped_key_is_deterministic(): void
    {
        $ref = new \ReflectionMethod(KeyGenerator::class, 'deriveScopedKey');
        $ref->setAccessible(true);

        $parentKey = 'test-admin-key-1234567890ab';
        $params = [
            'filter_by' => 'blog_id:=1',
            'expires_at' => 1712865600,
        ];

        $key1 = $ref->invoke(null, $parentKey, $params);
        $key2 = $ref->invoke(null, $parentKey, $params);

        $this->assertEquals($key1, $key2);
    }

    public function test_derive_scoped_key_differs_for_different_params(): void
    {
        $ref = new \ReflectionMethod(KeyGenerator::class, 'deriveScopedKey');
        $ref->setAccessible(true);

        $parentKey = 'test-admin-key-1234567890ab';

        $key1 = $ref->invoke(null, $parentKey, [
            'filter_by' => 'blog_id:=1',
            'expires_at' => 1712865600,
        ]);

        $key2 = $ref->invoke(null, $parentKey, [
            'filter_by' => 'blog_id:=2',
            'expires_at' => 1712865600,
        ]);

        $this->assertNotEquals($key1, $key2);
    }

    public function test_derive_scoped_key_is_base64(): void
    {
        $ref = new \ReflectionMethod(KeyGenerator::class, 'deriveScopedKey');
        $ref->setAccessible(true);

        $parentKey = 'test-admin-key-1234567890ab';
        $params = ['expires_at' => time() + 3600];

        $key = $ref->invoke(null, $parentKey, $params);

        $decoded = base64_decode($key, true);
        $this->assertNotFalse($decoded);
        $this->assertGreaterThan(10, strlen($decoded));
    }

    public function test_build_admin_filter_with_single_blog(): void
    {
        $filter = KeyGenerator::buildAdminFilter([5]);

        $this->assertEquals('blog_id:=[5]', $filter);
    }

    public function test_build_admin_filter_with_multiple_blogs(): void
    {
        $filter = KeyGenerator::buildAdminFilter([2, 5, 10]);

        $this->assertEquals('blog_id:=[2,5,10]', $filter);
    }
}
