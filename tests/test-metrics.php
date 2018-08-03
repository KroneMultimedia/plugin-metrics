<?php
/**
* @covers KMM\Metrics\Core
*/
use KMM\Metrics\Core;
use phpmock\MockBuilder;


class TestMetrics extends \WP_UnitTestCase
{
    public function setUp()
    {
        # setup a rest server
        parent::setUp();
        $this->core = new Core('i18n');
    }

    /**
    * @test
    */
    public function sample() {
      $this->assertEquals(1,1);
    }


    public function tearDown()
    {
        parent::tearDown();
    }
}
