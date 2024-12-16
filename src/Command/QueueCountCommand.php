<?php

namespace eLife\Bus\Command;

use eLife\Bus\Queue\WatchableQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueCountCommand extends Command
{
    private $queue;

    public function __construct(WatchableQueue $queue)
    {
        parent::__construct();

        $this->queue = $queue;
    }

    protected function configure()
    {
        $this
            ->setName('queue:count')
            ->setDescription('Counts the SQS messages, including invisible ones. Approximate.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->queue->count());

        return 0;
    }
}
