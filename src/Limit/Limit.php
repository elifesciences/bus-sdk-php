<?php

namespace eLife\Bus\Limit;

interface Limit
{
    public function hasBeenReached() : bool;

    /**
     * @deprecated  use hasBeenReached() instead
     */
    public function __invoke() : bool;

    public function getReasons() : array;
}
