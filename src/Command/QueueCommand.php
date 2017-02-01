<?php
/**
 * Queue command.
 *
 * For listening to SQS.
 */

namespace eLife\Bus\Command;

use eLife\ApiClient\Exception\BadResponse;
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
        callable $limit,
        bool $serializedTransform = true
    ) {
        $this->logger = $logger;
        $this->queue = $queue;
        $this->monitoring = $monitoring;
        $this->transformer = $transformer;
        $this->limit = $limit;
        $this->serializedTransform = $serializedTransform;

        parent::__construct(null);
    }

    abstract protected function process(InputInterface $input, QueueItem $item, $entity = null);

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Watches SQS for changes to articles, ');
    }

    final public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info($this->getName().' Started listening.');
        $this->monitoring->nameTransaction($this->getName());
        // Loop.
        $limit = $this->limit;
        while (!$limit()) {
            $this->loop($input);
        }
        $this->logger->info($this->getName().' Stopped because of limits reached.');
    }

    public function transform(QueueItem $item)
    {
        $entity = null;
        try {
            // Transform into something for gearman.
            $entity = $this->transformer->transform($item, $this->serializedTransform);
        } catch (BadResponse $e) {
            // We got a 404 or server error.
            $this->logger->error("{$this->getName()}: Item does not exist in API: {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            // Remove from queue.
            $this->queue->commit($item);
        } catch (Throwable $e) {
            // Unknown error.
            $this->logger->error("{$this->getName()}: There was an unknown problem importing {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            $this->monitoring->recordException($e, "Error in importing {$item->getType()} {$item->getId()}");
            // Remove from queue.
            $this->queue->commit($item);
        }

        return $entity;
    }

    final private function loop(InputInterface $input)
    {
        $this->logger->debug($this->getName().' Loop start, listening to queue', ['queue' => (string) $this->queue]);
        $item = $this->queue->dequeue();
        if ($item) {
            $this->monitoring->startTransaction();
            if ($entity = $this->transform($item)) {
                $this->process($input, $item, $entity);
            }
            $this->monitoring->endTransaction();
        }
        $this->logger->debug($this->getName().' End of loop');
    }
}
