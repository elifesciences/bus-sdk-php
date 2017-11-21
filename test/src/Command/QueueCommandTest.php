<?php

namespace test\eLife\Bus\Command;

use eLife\ApiClient\Exception\BadResponse;
use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Limit\CallbackLimit;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Logging\Monitoring;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
    private $logger;
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
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->transformer = $this->createMock(QueueItemTransformer::class);
        $this->transformer
            ->expects($this->any())
            ->method('transform')
            ->will($this->returnValue(['field' => 'value']));
        $this->command = new ApplicationSpecificQueueCommand(
            $this->logger,
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

    /**
     * @test
     */
    public function it_will_remove_item_from_queue_if_transformer_fails()
    {
        $transformer = $this->createMock(QueueItemTransformer::class);
        $transformer
            ->expects($this->any())
            ->method('transform')
            ->will($this->throwException(new Exception('Unknown type of entity')));
        $command = new ApplicationSpecificQueueCommand(
            $this->logger,
            $this->queue,
            $transformer,
            new Monitoring(),
            $this->limitIterations(1)
        );
        $this->application->add($command);
        $this->queue->enqueue(new InternalSqsMessage('article', '42'));
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('queue:watch: There was an unknown problem transforming article (42)', ['exception' => new Exception('Unknown type of entity'), 'item' => new InternalSqsMessage('article', '42')]);
        $command_tester = new CommandTester($this->application->get($command->getName()));
        $command_tester->execute(['command' => $command->getName()]);
        $this->assertEquals(0, $this->queue->count(), 'Expected an empty queue');
    }

    /**
     * @test
     */
    public function explains_known_api_failures_from_transformer()
    {
        $transformer = $this->createMock(QueueItemTransformer::class);
        $transformer
            ->expects($this->any())
            ->method('transform')
            ->will($this->throwException(new BadResponse('Fail to retrieve data', new Request('GET', '/something/42'), new Response(404))));
        $command = new ApplicationSpecificQueueCommand(
            $this->logger,
            $this->queue,
            $transformer,
            new Monitoring(),
            $this->limitIterations(1)
        );
        $this->application->add($command);
        $this->queue->enqueue(new InternalSqsMessage('article', '42'));
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('queue:watch: Item does not exist in API: article (42)', ['exception' => new BadResponse('Fail to retrieve data', new Request('GET', '/something/42'), new Response(404)), 'item' => new InternalSqsMessage('article', '42')]);
        $command_tester = new CommandTester($this->application->get($command->getName()));
        $command_tester->execute(['command' => $command->getName()]);
        $this->assertEquals(0, $this->queue->count(), 'Expected an empty queue');
    }

    /**
     * @test
     */
    public function it_will_remove_item_from_queue_if_process_fails()
    {
        $command = new ProcessingErrorQueueCommand(
            $this->logger,
            $this->queue,
            $this->transformer,
            new Monitoring(),
            $this->limitIterations(1)
        );
        $this->application->add($command);
        $this->queue->enqueue(new InternalSqsMessage('article', '42'));
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('queue:watch: There was an unknown problem processing article (42)', ['exception' => new Exception('Fail gracefully'), 'item' => new InternalSqsMessage('article', '42')]);
        $command_tester = new CommandTester($this->application->get($command->getName()));
        $command_tester->execute(['command' => $command->getName()]);
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

class ProcessingErrorQueueCommand extends ApplicationSpecificQueueCommand
{
    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
        throw new Exception('Fail gracefully');
    }
}
