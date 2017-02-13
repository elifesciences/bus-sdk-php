<?php

namespace eLife\Bus\Queue;

interface SingleItemRepository
{
    public function get(string $id, string $type);
}
