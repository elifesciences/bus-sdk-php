<?php

namespace eLife\Bus\Queue;

interface QueueItemTransformer
{
    public function transform(QueueItem $item);
}
