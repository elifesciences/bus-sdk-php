<?php

namespace tests\eLife\Bus\Limit;

use eLife\Bus\Limit\CompositeLimit;
use PHPUnit_Framework_TestCase;

class CompositeLimitTest extends PHPUnit_Framework_TestCase
{
    public function test_composite_limit_failure()
    {
        $fail = new BasicLimitMock(true);
        $pass = new BasicLimitMock();
        $limit = new CompositeLimit($fail, $pass);

        $this->assertTrue($limit());

        $this->assertEquals(['This is the reason it failed'], $limit->getReasons());
    }

    public function test_composite_limit_multiple_failures()
    {
        $fail = new BasicLimitMock(true, ['failure 1']);
        $fail2 = new BasicLimitMock(true, ['failure 2']);
        $fail3 = new BasicLimitMock(true, ['failure 3', 'failure 4']);
        $pass = new BasicLimitMock();
        $limit = new CompositeLimit($fail, $fail2, $fail3, $pass);

        $this->assertTrue($limit());

        $this->assertEquals([
            'failure 1',
            'failure 2',
            'failure 3',
            'failure 4',
        ], $limit->getReasons());
    }

    public function test_composite_limit_pass()
    {
        $pass = new BasicLimitMock();
        $pass2 = new BasicLimitMock();
        $limit = new CompositeLimit($pass, $pass2);

        $this->assertFalse($limit());

        $this->assertEquals([], $limit->getReasons());
    }
}
