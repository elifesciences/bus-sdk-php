<?php

namespace eLife\Bus\Queue;

interface QueueItemTransformer
{
    /**
     * Transforms QueueItem into either serialized or unserialized representation of the data.
     *
     * @param QueueItem $item       Item from Queue (typically type + id)
     * @param bool      $serialized True to expect serialized representation of transformation
     *
     * @return mixed JSON string, DTO or domain object should be returned from a transform
     */
    public function transform(QueueItem $item, bool $serialized = true);
}
