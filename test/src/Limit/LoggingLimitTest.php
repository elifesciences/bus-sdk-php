<?php

namespace test\eLife\Bus\Limit;

use eLife\Bus\Limit\LoggingLimit;
use eLife\Bus\Limit\MockLimit;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LoggingLimitTest extends TestCase
{
    public function test_logging_reasons()
    {
        $fail = new MockLimit(true);
        $logger = $this->createMock(LoggerInterface::class);
        $limit = new LoggingLimit($fail, $logger);

        $logger->expects($this->once())
            ->method('info')
            ->with('This is the reason it failed');
        $this->assertTrue($limit->hasBeenReached());
        $this->assertEquals(['This is the reason it failed'], $limit->getReasons());
    }
}
