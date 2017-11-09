<?php

namespace eLife\Bus\Limit;

final class CompositeLimit implements Limit
{
    private $reasons = [];
    private $limits = [];

    public function __construct(Limit ...$args)
    {
        $this->limits = $args;
    }

    public function hasBeenReached() : bool
    {
        $limitReached = false;
        foreach ($this->limits as $limit) {
            $failure = $limit->hasBeenReached();
            if ($failure) {
                $this->reasons = array_merge($this->reasons, $limit->getReasons());
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
        error_log('Using '.__CLASS__.' as a callable is deprecated. Use CallbackLimit:: hasBeenReached() instead.', E_USER_ERROR);

        return $this->hasBeenReached();
    }

    public function getReasons() : array
    {
        return $this->reasons;
    }
}
