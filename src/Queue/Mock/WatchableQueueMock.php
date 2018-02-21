<?php

namespace eLife\Bus\Queue\Mock;

use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\WatchableQueue;
use LogicException;

final class WatchableQueueMock implements WatchableQueue
{
    private $items = [];
    private $invisibleItems = [];

    public function __construct(QueueItem ...$items)
    {
        $this->items = $items;
    }

    /**
     * Adds item to the queue.
     *
     * Mock: Add item to queue.
     */
    public function enqueue(QueueItem $item)
    {
        array_push($this->items, $item);
    }

    /**
     * Mock: Move to separate "in progress" queue.
     */
    public function dequeue()
    {
        if ($this->items === []) {
            throw new LogicException('You should not reach a dequeue() on an empty queue inside tests');
        }
        $item = array_pop($this->items);

        return $this->invisibleItems[$item->getReceipt()] = $item;
    }

    /**
     * Mock: Remove item completely.
     */
    public function commit(QueueItem $item)
    {
        unset($this->invisibleItems[$item->getReceipt()]);
    }

    /**
     * Mock: re-add to queue.
     */
    public function release(QueueItem $item) : bool
    {
        array_unshift($this->items, $item);
        unset($this->invisibleItems[$item->getReceipt()]);

        return true;
    }

    public function clean()
    {
        $this->items = [];
    }

    public function count() : int
    {
        return count($this->items) + count($this->invisibleItems);
    }

    public function getName() : string
    {
        return 'mocked-queue';
    }
}
