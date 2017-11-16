<?php

namespace tests\eLife\Bus\Command;

use eLife\Bus\Command\QueueCleanCommand;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Bus\Command\QueueCleanCommand
 */
class QueueCleanCommandTest extends TestCase
{
    /** @var Application */
    private $application;
    /** @var QueueCleanCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    /** @var WatchableQueueMock */
    private $queue;

    /**
     * @before
     */
    public function setup_command()
    {
        $this->application = new Application();
        $this->queue = new WatchableQueueMock();
        $this->command = new QueueCleanCommand(
            $this->queue,
            $this->createMock(LoggerInterface::class)
        );
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($this->application->get($this->command->getName()));
    }

    /**
     * @test
     */
    public function it_will_clean_a_queue()
    {
        $this->queue->enqueue(new InternalSqsMessage('article', '42'));
        $this->queue->enqueue(new InternalSqsMessage('article', '43'));
        $this->assertEquals(2, $this->queue->count());
        $this->commandTester->execute(['command' => $this->command->getName()]);
        $this->assertEquals(0, $this->queue->count());
    }
}
