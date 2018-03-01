<?php

namespace eLife\Bus\Queue;

final class BusSqsMessage implements QueueItem
{
    private $id;
    private $type;
    private $receipt;
    private $messageId;
    private $attempts;

    public function __construct(string $messageId, string $id, string $type, string $receipt, int $attempts = 0)
    {
        $this->messageId = $messageId;
        $this->id = $id;
        $this->type = $type;
        $this->receipt = $receipt;
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
        return $this->receipt;
    }

    public function getAttempts() : int
    {
        return $this->attempts;
    }
}
