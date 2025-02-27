<?php

/**
 * @covers \KMM\Metrics\Core
 */
use KMM\Metrics\Core;

class TestMetrics extends WP_UnitTestCase
{
    public function setUp(): void
    {
        // setup a rest server
        parent::setUp();
        $this->core = new Core('i18n');
    }

    /**
     * @test
     */
    public function sample()
    {
        $this->assertEquals(1, 1);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
