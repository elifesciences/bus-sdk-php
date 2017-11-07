<?php

namespace eLife\Bus\Limit;

final class CallbackLimit implements Limit
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function hasBeenReached() : bool
    {
        return call_user_func($this->callback);
    }

    /**
     * @deprecated  use hasBeenReached() instead
     */
    public function __invoke() : bool
    {
        return $this->hasBeenReached();
    }

    public function getReasons() : array
    {
        return [];
    }
}
