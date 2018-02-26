<?php

namespace eLife\Bus\Queue;

final class InternalSqsMessage implements QueueItem
{
    private $type;
    private $id;
    private $attempts;

    public function __construct(string $type, string $id, int $attempts = 0)
    {
        $this->type = $type;
        $this->id = $id;
        $this->attempts = $attempts;
    }

    /**
     * Id or Number.
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Type (Article, Collection, Event etc.).
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * SQS ReceiptHandle.
     */
    public function getReceipt() : string
    {
        return $this->type.'--'.$this->id;
    }

    public function getAttempts() : int
    {
        return $this->attempts;
    }
}
