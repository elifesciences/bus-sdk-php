<?php

namespace eLife\Bus\Queue;

final class BusSqsMessageFactory
{
    public static function create(string $messageId, string $id, string $type, string $receipt) : QueueItem
    {
        return new BusSqsMessage($messageId, $id, $type, $receipt);
    }
}
