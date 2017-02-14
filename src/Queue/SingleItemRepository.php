<?php

namespace eLife\Bus\Queue;

interface SingleItemRepository
{
    /**
     * Get single entity.
     *
     * This method will return an API SDK item when given an ID and Type.
     * Implementations may use some sort of caching depending on implementation
     * details.
     *
     * @return mixed
     */
    public function get(string $type, string $id);
}
