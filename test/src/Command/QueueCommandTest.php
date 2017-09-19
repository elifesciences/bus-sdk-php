<?php

namespace eLife\Bus\Command;

use eLife\Bus\Limit\CallbackLimit;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Logging\Monitoring;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueCommandTest extends TestCase
{
    private $queue;
    private $transformer;
    private $command;
    private $input;
    private $output;

    public function setUp()
    {
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
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
    }

    public function testDeletesMessagesFromTheQueueAfterProcessing()
    {
        $message = new InternalSqsMessage('article', '42');
        $this->queue->enqueue($message);
        $this->command->execute($this->input, $this->output);
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
