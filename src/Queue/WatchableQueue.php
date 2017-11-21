<?php

namespace eLife\Bus\Queue;

use Countable;

interface WatchableQueue extends Countable
{
    /**
     * Adds item to the queue.
     */
    public function enqueue(QueueItem $item);

    /**
     * Gets an item to process.
     *
     * @return QueueItem|null
     */
    public function dequeue();

    /**
     * Commits to removing item from queue, marks item as done and processed.
     */
    public function commit(QueueItem $item);

    /**
     * Deletes everything from the queue.
     */
    public function clean();

    public function getName() : string;
}
