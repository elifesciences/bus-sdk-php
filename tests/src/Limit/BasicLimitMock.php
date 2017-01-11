<?php

namespace tests\eLife\Bus\Limit;

use eLife\Bus\Limit\Limit;

class BasicLimitMock implements Limit
{
    private $fail;
    private $messages;

    public function fail()
    {
        $this->fail = true;
    }

    public function __construct($fail = false, $messages = ['This is the reason it failed'])
    {
        $this->fail = $fail;
        $this->messages = $messages;
    }

    public function __invoke(): bool
    {
        return $this->fail;
    }

    public function getReasons(): array
    {
        return $this->messages;
    }
}
