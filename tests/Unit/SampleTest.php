<?php
namespace Tests\Feature;

use Tests\TestCase;

class SampleTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
    }

    /**
     * @test
     */
    public function sampleTestCase() : void {
        $this->assertTrue(true);
    }
}
