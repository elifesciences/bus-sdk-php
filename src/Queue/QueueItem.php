<?php

namespace eLife\Bus\Queue;

interface QueueItem
{
    /**
     * Id or Number.
     */
    public function getId() : string;

    /**
     * Type (Article, Collection, Event etc.).
     */
    public function getType() : string;

    /**
     * SQS ReceiptHandle.
     */
    public function getReceipt() : string;
}
