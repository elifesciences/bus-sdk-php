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
     * - throw an exception in case of error
     * In both cases the message will be removed. Only if the process crashes the item will be retried later.
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
        $this->monitoring->nameTransaction($this->getName());
        while (!$this->limit->hasBeenReached()) {
            $this->loop($input);
        }
        $this->logger->info($this->getName().' Stopped because of limits reached.');
    }

    final protected function transform(QueueItem $item)
    {
        $entity = null;
        $entity = $this->transformer->transform($item, $this->serializedTransform);

        return $entity;
    }

    private function loop(InputInterface $input)
    {
        $this->logger->debug($this->getName().' Loop start, listening to queue', ['queue' => $this->queue->getName()]);
        $item = $this->queue->dequeue();
        if ($item) {
            try {
                $this->monitoring->startTransaction();
                if ($entity = $this->transform($item)) {
                    $this->process($input, $item, $entity);
                }
                $this->monitoring->endTransaction();
            } catch (BadResponse $e) {
                // We got a 404 or server error.
                $this->logger->error("{$this->getName()}: Item does not exist in API: {$item->getType()} ({$item->getId()})", [
                    'exception' => $e,
                    'item' => $item,
                ]);
            } catch (Throwable $e) {
                $this->logger->error("{$this->getName()}: There was an unknown problem processing {$item->getType()} ({$item->getId()})", [
                    'exception' => $e,
                    'item' => $item,
                ]);
                $this->monitoring->recordException($e, "Error in processing {$item->getType()} {$item->getId()}");
            } finally {
                $this->queue->commit($item);
            }
        }
        $this->logger->debug($this->getName().' End of loop');
    }
}
