<?php

namespace eLife\Bus\Queue;

use Doctrine\Common\Cache\Cache;
use eLife\ApiSdk\ApiSdk;
use Psr\Log\LoggerInterface;

class CachedTransformer implements QueueItemTransformer
{
    use BasicTransformer;

    private $cache;
    private $logger;
    private $lifetime;
    private $sdk;
    private $serializer;
    private $shouldCache;

    public function __construct(
        ApiSdk $sdk,
        Cache $cache,
        LoggerInterface $logger,
        int $lifetime,
        callable $shouldCache = null
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->lifetime = $lifetime;
        $this->shouldCache = $shouldCache;
    }

    private function shouldCacheEntity(string $type, string $id)
    {
        if ($shouldCache = $this->shouldCache) {
            return $shouldCache($type, $id);
        }

        return true;
    }

    public function get($id, $type)
    {
        if ($this->shouldCacheEntity($id, $type) === false) {
            return $this->refresh(new InternalSqsMessage($type, $id));
        }
        $key = $this->getKey($id, $type);
        $this->logger->debug('Fetching from cache', [
            'id' => $id,
            'type' => $type,
            'key' => $key,
        ]);

        return $this->cache->fetch($key);
    }

    public function refresh(QueueItem $item)
    {
        $sdk = $this->getSdk($item);
        $entity = $sdk->get($item->getId())->wait(true);
        if ($this->shouldCacheEntity($item->getType(), $item->getId()) === false) {
            return $entity;
        }
        if ($entity) {
            $this->logger->debug('Saving to cache', [
                'id' => $item->getId(),
                'type' => $item->getType(),
            ]);
            $this->cache->save(
                $this->getKeyFromQueueItem($item),
                $entity,
                $this->lifetime
            );
        } else {
            $this->logger->debug('404 from SDK', [
                'id' => $item->getId(),
                'type' => $item->getType(),
            ]);
        }

        return $entity;
    }

    protected function getKeyFromQueueItem(QueueItem $item) : string
    {
        return static::getKey($item->getType(), $item->getId());
    }

    public static function getKey(string $type, string $id) : string
    {
        return sha1(sha1($id), sha1($type));
    }

    public function transform(QueueItem $item, bool $serialized = true)
    {
        $entity = $this->refresh($item);
        if ($serialized === false) {
            return $entity;
        }

        return $this->serializer->serialize($entity, 'json');
    }
}
