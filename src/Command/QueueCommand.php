<?php

namespace eLife\Bus\Command;

use eLife\ApiClient\Exception\BadResponse;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class QueueCommand extends Command
{
    protected $logger;
    protected $queue;
    protected $transformer;
    protected $limit;
    protected $monitoring;
    private $serializedTransform;

    public function __construct(
        LoggerInterface $logger,
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        Monitoring $monitoring,
        Limit $limit,
        bool $serializedTransform = true
    ) {
        $this->logger = $logger;
        $this->queue = $queue;
        $this->monitoring = $monitoring;
        $this->transformer = $transformer;
        $this->limit = $limit;
        $this->serializedTransform = $serializedTransform;

        parent::__construct();
    }

    /**
     * You never need to call commit().
     * Possible things to do:
     * - perform work on $item/$entity
     * - throw an exception in case of error.
     */
    abstract protected function process(InputInterface $input, QueueItem $item, $entity = null);

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Watches SQS for changes.');
    }

    final public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info($this->getName().' Started listening.');
        while (!$this->limit->hasBeenReached()) {
            $this->loop($input);
        }
        $this->logger->info($this->getName().' Stopped because of limits reached.');
    }

    final protected function transform(QueueItem $item)
    {
        $entity = null;
        try {
            // Transform into something for gearman.
            $entity = $this->transformer->transform($item, $this->serializedTransform);
        } catch (Throwable $e) {
            if ($e instanceof BadResponse && in_array($e->getResponse()->getStatusCode(), [404, 410])) {
                $this->logger->error("{$this->getName()}: Item does not exist in API: {$item->getType()} ({$item->getId()})", [
                    'exception' => $e,
                    'item' => $item,
                ]);
                // Remove from queue.
                $this->queue->commit($item);
            } else {
                // Unknown error.
                $this->logger->error("{$this->getName()}: There was an unknown problem importing {$item->getType()} ({$item->getId()})", [
                    'exception' => $e,
                    'item' => $item,
                ]);
                $this->monitoring->recordException($e, "Error in importing {$item->getType()} {$item->getId()}");
                $this->release($item);
            }
        }

        return $entity;
    }

    private function loop(InputInterface $input)
    {
        $this->logger->debug($this->getName().' Loop start, listening to queue', ['queue' => $this->queue->getName()]);
        $item = $this->queue->dequeue();
        if ($item) {
            $this->monitoring->startTransaction();
            $this->monitoring->nameTransaction($this->getName());
            if ($entity = $this->transform($item)) {
                try {
                    $this->process($input, $item, $entity);
                    // Remove from queue.
                    $this->queue->commit($item);
                } catch (Throwable $e) {
                    $this->logger->error("{$this->getName()}: There was an unknown problem processing {$item->getType()} ({$item->getId()})", [
                        'exception' => $e,
                        'item' => $item,
                    ]);
                    $this->monitoring->recordException($e, "Error in processing {$item->getType()} {$item->getId()}");
                    $this->release($item);
                }
            }
            $this->monitoring->endTransaction();
        }
        $this->logger->debug($this->getName().' End of loop');
    }

    private function release(QueueItem $item)
    {
        try {
            $this->queue->release($item);
        } catch (Throwable $e) {
            $this->logger->critical("{$this->getName()}: Failed to release {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            $this->monitoring->recordException($e, "Error in releasing {$item->getType()} {$item->getId()}");
        }
    }
}
