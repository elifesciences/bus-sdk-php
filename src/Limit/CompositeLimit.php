<?php

namespace eLife\Bus\Limit;

final class CompositeLimit implements Limit
{
    private $reasons = [];
    private $functions = [];

    public function __construct(Limit ...$args)
    {
        $this->functions = $args;
    }

    public function hasBeenReached() : bool
    {
        $limitReached = false;
        foreach ($this->functions as $fn) {
            $failure = $fn();
            if ($failure) {
                $this->reasons = array_merge($this->reasons, $fn->getReasons());
                $limitReached = true;
            }
        }

        return $limitReached;
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
        return $this->reasons;
    }
}
