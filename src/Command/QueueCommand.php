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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class QueueCommand extends Command
{
    protected $logger;
    protected $queue;
    protected $transformer;
    protected $limit;
    protected $monitoring;
    protected $topic;

    public function __construct(string $topic, LoggerInterface $logger, WatchableQueue $queue, QueueItemTransformer $transformer, Monitoring $monitoring, callable $limit)
    {
        $this->logger = $logger;
        $this->queue = $queue;
        $this->monitoring = $monitoring;
        $this->transformer = $transformer;
        $this->limit = $limit;
        $this->topic = $topic;

        parent::__construct(null);
    }

    /**
     * @implementation
     */
    abstract protected function process(QueueItem $item);

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Watches SQS for changes to articles, ')
            ->addOption('drop', 'd', InputOption::VALUE_NONE);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('queue:watch: Started listening.');
        $this->monitoring->nameTransaction('queue:watch');
        // Loop.
        $limit = $this->limit;
        while (!$limit()) {
            $this->loop($input);
        }
        $this->logger->info('queue:watch: Stopped because of limits reached.');
    }

    /**
     * @shared
     */
    public function transform(QueueItem $item)
    {
        $entity = null;
        try {
            // Transform into something for gearman.
            $entity = $this->transformer->transform($item);
        } catch (BadResponse $e) {
            // We got a 404 or server error.
            $this->logger->error("queue:watch: Item does not exist in API: {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            // Remove from queue.
            $this->queue->commit($item);
        } catch (Throwable $e) {
            // Unknown error.
            $this->logger->error("queue:watch: There was an unknown problem importing {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            $this->monitoring->recordException($e, "Error in importing {$item->getType()} {$item->getId()}");
            // Remove from queue.
            $this->queue->commit($item);
        }

        return $entity;
    }

    /**
     * @shared
     */
    public function loop(InputInterface $input)
    {
        $this->logger->debug('queue:watch: Loop start, listening to queue', ['queue' => $this->topic]);
        $item = $this->queue->dequeue();
        if ($item) {
            $this->monitoring->startTransaction();
            if ($entity = $this->transform($item)) {
                $this->process($item);
            }
            $this->monitoring->endTransaction();
        }
        $this->logger->debug('queue:watch: End of loop');
    }
}
