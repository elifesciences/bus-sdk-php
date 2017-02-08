<?php

namespace eLife\Bus\Queue;

use LogicException;

class CachedSqsMessageTransformer implements QueueItemTransformer
{
    private $transformer;

    public function __construct(CachedTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function transform(QueueItem $item, bool $serialized = true)
    {
        return $this->transformer->transform($item, $serialized);
    }

    public static function fromMessage($message) : QueueItem
    {
        $message = array_shift($message['Messages']);
        $messageId = $message['MessageId'];
        $body = json_decode($message['Body']);
        $md5 = $message['MD5OfBody'];
        $handle = $message['ReceiptHandle'];
        if (md5($message['Body']) !== $md5) {
            throw new LogicException('Hash mismatch: possible corrupted message.');
        }

        return new BusSqsMessage($messageId, $body->id ?? $body->number, $body->type, $handle);
    }

    public static function hasItems(array $message)
    {
        // If Messages exists and is not empty.
        return isset($message['Messages']) ? (empty($message['Messages']) ? false : true) : false;
    }
}
