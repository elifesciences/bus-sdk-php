<?php

namespace eLife\Bus\Queue;

final class InternalSqsMessageFactory
{
    public static function create(string $type, string $id) : QueueItem
    {
        return new InternalSqsMessage($type, $id);
    }
}
