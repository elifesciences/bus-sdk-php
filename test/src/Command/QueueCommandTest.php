<?php

namespace test\eLife\Bus\Command;

use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Limit\CallbackLimit;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Logging\Monitoring;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Bus\Command\QueueCommand
 */
final class QueueCommandTest extends TestCase
{
    /** @var Application */
    private $application;
    /** @var ApplicationSpecificQueueCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    private $transformer;
    /** @var WatchableQueueMock */
    private $queue;

    /**
     * @before
     */
    public function setup_command()
    {
        $this->application = new Application();
        $this->queue = new WatchableQueueMock();
        $this->transformer = $this->createMock(QueueItemTransformer::class);
        $this->transformer
            ->expects($this->any())
            ->method('transform')
            ->will($this->returnValue(['field' => 'value']));
        $this->command = new ApplicationSpecificQueueCommand(
            $this->createMock(LoggerInterface::class),
            $this->queue,
            $this->transformer,
            new Monitoring(),
            $this->limitIterations(1)
        );
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($this->application->get($this->command->getName()));
    }

    /**
     * @test
     */
    public function it_will_delete_messages_from_queue_after_processing()
    {
        $message = new InternalSqsMessage('article', '42');
        $this->queue->enqueue($message);
        $this->commandTester->execute(['command' => $this->command->getName()]);
        $this->assertEquals(0, $this->queue->count(), 'Expected an empty queue');
    }

    private function limitIterations(int $number) : Limit
    {
        $iterationCounter = 0;

        return new CallbackLimit(function () use ($number, &$iterationCounter) {
            ++$iterationCounter;
            if ($iterationCounter > $number) {
                return true;
            }

            return false;
        });
    }
}

class ApplicationSpecificQueueCommand extends QueueCommand
{
    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
    }
}
