<?php

namespace TheoryTest\ADI\Tests;

use TheoryTest\ADI\Tests\SetUp;
use TheoryTest\ADI\TheoryTest;

class TheoryTestTest extends SetUp
{
    protected $theoryTest;
    
    protected function setUp() : void
    {
        parent::setUp();
        $this->theoryTest = new TheoryTest($this->db, $this->config, $this->template, $this->user);
    }
    
    public function testExample()
    {
        $this->markTestIncomplete();
    }
}
