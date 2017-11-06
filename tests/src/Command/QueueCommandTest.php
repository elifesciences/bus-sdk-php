<?php

namespace eLife\Bus\Command;

use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Logging\Monitoring;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommandTest extends \PHPUnit_Framework_TestCase
{
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

    private function limitIterations($number)
    {
        $this->iterationCounter = 0;

        return function () use ($number) {
            ++$this->iterationCounter;
            if ($this->iterationCounter > $number) {
                return true;
            }

            return false;
        };
    }
}

class ApplicationSpecificQueueCommand extends QueueCommand
{
    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
    }
}
