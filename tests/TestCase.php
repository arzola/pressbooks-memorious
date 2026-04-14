<?php

namespace Tests;

use PressbooksMemorious\Bootstrap;
use PressbooksMemorious\Database\Migration;
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
