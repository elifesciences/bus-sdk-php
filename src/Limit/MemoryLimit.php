<?php

namespace eLife\Bus\Limit;

final class MemoryLimit implements Limit
{
    private $bytes;
    private $actualBytes;

    private function __construct(int $bytes)
    {
        $this->bytes = $bytes;
    }

    public static function mb(int $megabytes) : self
    {
        return new self($megabytes * 1024 * 1024);
    }

    public function hasBeenReached() : bool
    {
        $this->actualBytes = memory_get_usage(true);
        if ($this->actualBytes > $this->bytes) {
            return true;
        }

        return false;
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
        return ["Memory limit exceeded: {$this->bytes} bytes exceeds {$this->actualBytes} bytes"];
    }
}
