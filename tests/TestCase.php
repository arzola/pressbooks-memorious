<?php

namespace Tests;

use PressbooksBeacon\Bootstrap;
use PressbooksBeacon\Database\Migration;
use WP_UnitTestCase;

class TestCase extends WP_UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Migration::migrate();

        (new Bootstrap)->setUp();
    }
}
