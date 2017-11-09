<?php

namespace eLife\Bus\Limit;

use Psr\Log\LoggerInterface;

final class LoggingLimit implements Limit
{
    private $limit;
    private $logger;
    private $reasons;

    public function __construct(Limit $limit, LoggerInterface $logger)
    {
        $this->limit = $limit;
        $this->logger = $logger;
    }

    public function hasBeenReached() : bool
    {
        $limitReached = $this->limit->hasBeenReached();
        if ($limitReached) {
            $this->reasons = $this->limit->getReasons();
            foreach ($this->reasons as $reason) {
                $this->logger->info($reason);
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
