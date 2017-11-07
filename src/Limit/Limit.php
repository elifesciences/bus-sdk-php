<?php

namespace eLife\Bus\Limit;

interface Limit
{
    public function __invoke() : bool;

    public function getReasons() : array;
}
