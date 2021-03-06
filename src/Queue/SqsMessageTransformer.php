<?php

namespace eLife\Bus\Queue;

use eLife\ApiSdk\ApiSdk;
use LogicException;

final class SqsMessageTransformer implements QueueItemTransformer, SingleItemRepository
{
    use BasicTransformer;

    private $sdk;
    private $serializer;

    public function __construct(
        ApiSdk $sdk
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
    }

    public static function fromMessage($message) : QueueItem
    {
        $message = array_shift($message['Messages']);
        $messageId = $message['MessageId'];
        $body = json_decode($message['Body']);
        $md5 = $message['MD5OfBody'];
        $handle = $message['ReceiptHandle'];
        $attempts = (int) ($message['Attributes']['ApproximateReceiveCount'] ?? 0);
        if (md5($message['Body']) !== $md5) {
            throw new LogicException('Hash mismatch: possible corrupted message.');
        }

        return new BusSqsMessage($messageId, $body->id ?? $body->number, $body->type, $handle, $attempts);
    }

    public static function hasItems(array $message) : bool
    {
        // If Messages exists and is not empty.
        return isset($message['Messages']) ? (empty($message['Messages']) ? false : true) : false;
    }
}
