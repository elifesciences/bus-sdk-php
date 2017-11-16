<?php

namespace tests\eLife\Bus\Command;

use eLife\Bus\Command\QueueCountCommand;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Bus\Command\QueueCountCommand
 */
class QueueCountCommandTest extends TestCase
{
    /** @var Application */
    private $application;
    /** @var QueueCountCommand */
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
        $this->command = new QueueCountCommand(
            $this->queue
        );
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($this->application->get($this->command->getName()));
    }

    /**
     * @test
     */
    public function it_will_count_queue_items()
    {
        $this->commandTester->execute(['command' => $this->command->getName()]);
        $display = trim($this->commandTester->getDisplay());
        $this->assertEquals('0', $display);
        $this->queue->enqueue(new InternalSqsMessage('article', '42'));
        $this->queue->enqueue(new InternalSqsMessage('article', '43'));
        $this->queue->enqueue(new InternalSqsMessage('article', '44'));
        $this->commandTester->execute(['command' => $this->command->getName()]);
        $display = trim($this->commandTester->getDisplay());
        $this->assertEquals('3', $display);
    }
}
