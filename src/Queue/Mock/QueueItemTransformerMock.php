<?php

namespace eLife\Bus\Queue\Mock;

use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Queue\BasicTransformer;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\SingleItemRepository;

final class QueueItemTransformerMock implements QueueItemTransformer, SingleItemRepository
{
    use BasicTransformer;

    private $sdk;
    private $serializer;

    public function __construct(
        ApiSdk $sdk
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
    }
}
