<?php

namespace eLife\Bus\Limit;

final class MockLimit implements Limit
{
    private $fail;
    private $messages;

    public function fail()
    {
        $this->fail = true;
    }

    public function __construct($fail = false, array $messages = ['This is the reason it failed'])
    {
        $this->fail = $fail;
        $this->messages = $messages;
    }

    public function hasBeenReached() : bool
    {
        return $this->fail;
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
        return $this->messages;
    }
}
