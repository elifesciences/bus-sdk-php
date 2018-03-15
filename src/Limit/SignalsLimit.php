<?php

namespace eLife\Bus\Limit;

final class SignalsLimit implements Limit
{
    private static $validSignals = [
        'SIGINT' => SIGINT,
        'SIGTERM' => SIGTERM,
        'SIGHUP' => SIGHUP,
    ];

    private $valid;
    private $reasons;

    public function __construct($signals)
    {
        foreach ($signals as $signal) {
            pcntl_signal(self::$validSignals[$signal], function () use ($signal) {
                $this->onTermination($signal);
            });
        }
    }

    public static function stopOn(array $signals) : self
    {
        return new static($signals);
    }

    public function hasBeenReached() : bool
    {
        pcntl_signal_dispatch();

        return false === $this->valid;
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

    private function onTermination(string $signal)
    {
        $this->reasons[] = "Received signal: $signal";
        $this->valid = false;
    }
}
