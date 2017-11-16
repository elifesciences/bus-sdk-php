<?php

namespace test\eLife\Bus\Limit;

use eLife\Bus\Limit\CompositeLimit;
use eLife\Bus\Limit\MockLimit;
use PHPUnit\Framework\TestCase;

/**
 * @covers \eLife\Bus\Limit\CompositeLimit
 */
final class CompositeLimitTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_fail()
    {
        $fail = new MockLimit(true);
        $pass = new MockLimit();
        $limit = new CompositeLimit($fail, $pass);

        $this->assertTrue($limit->hasBeenReached());

        $this->assertEquals(['This is the reason it failed'], $limit->getReasons());
    }

    /**
     * @test
     */
    public function it_can_fail_with_multiple_reasons()
    {
        $fail = new MockLimit(true, ['failure 1']);
        $fail2 = new MockLimit(true, ['failure 2']);
        $fail3 = new MockLimit(true, ['failure 3', 'failure 4']);
        $pass = new MockLimit();
        $limit = new CompositeLimit($fail, $fail2, $fail3, $pass);

        $this->assertTrue($limit->hasBeenReached());

        $this->assertEquals([
            'failure 1',
            'failure 2',
            'failure 3',
            'failure 4',
        ], $limit->getReasons());
    }

    /**
     * @test
     */
    public function it_can_pass()
    {
        $pass = new MockLimit();
        $pass2 = new MockLimit();
        $limit = new CompositeLimit($pass, $pass2);

        $this->assertFalse($limit->hasBeenReached());

        $this->assertEquals([], $limit->getReasons());
    }
}
