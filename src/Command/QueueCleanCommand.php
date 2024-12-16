<?php

namespace eLife\Bus\Command;

use eLife\Bus\Queue\WatchableQueue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueCleanCommand extends Command
{
    private $queue;
    private $logger;

    public function __construct(WatchableQueue $queue, LoggerInterface $logger)
    {
        parent::__construct();

        $this->queue = $queue;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('queue:clean')
            ->setDescription('Cleans the SQS queue through purging.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Cleaning queue: '.$this->queue->getName());
        $this->queue->clean();

        return 0;
    }
}
