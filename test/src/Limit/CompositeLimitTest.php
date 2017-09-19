<?php

namespace test\eLife\Bus\Limit;

use eLife\Bus\Limit\CompositeLimit;
use eLife\Bus\Limit\MockLimit;
use PHPUnit\Framework\TestCase;

final class CompositeLimitTest extends TestCase
{
    public function test_composite_limit_failure()
    {
        $fail = new MockLimit(true);
        $pass = new MockLimit();
        $limit = new CompositeLimit($fail, $pass);

        $this->assertTrue($limit());

        $this->assertEquals(['This is the reason it failed'], $limit->getReasons());
    }

    public function test_composite_limit_multiple_failures()
    {
        $fail = new MockLimit(true, ['failure 1']);
        $fail2 = new MockLimit(true, ['failure 2']);
        $fail3 = new MockLimit(true, ['failure 3', 'failure 4']);
        $pass = new MockLimit();
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
        $pass = new MockLimit();
        $pass2 = new MockLimit();
        $limit = new CompositeLimit($pass, $pass2);

        $this->assertFalse($limit());

        $this->assertEquals([], $limit->getReasons());
    }
}
