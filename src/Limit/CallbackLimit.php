<?php

namespace eLife\Bus\Limit;

final class CallbackLimit implements Limit
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke() : bool
    {
        return call_user_func($this->callback);
    }

    public function getReasons() : array
    {
        return [];
    }
}
