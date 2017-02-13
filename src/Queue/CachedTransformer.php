<?php

namespace eLife\Bus\Queue;

use Doctrine\Common\Cache\Cache;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Model\Model;
use Psr\Log\LoggerInterface;

class CachedTransformer implements QueueItemTransformer, SingleItemRepository
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

    /**
     * Get single entity.
     *
     * This method will return an API SDK item when given an ID and Type.
     * If the item should be cached ($this->shouldCache) then we check to
     * see if the item is in the cache.
     *
     * - If the item IS in the cache we return it, never hitting the ApiSDK.
     * - If the item IS NOT in the cache but it CAN be cached we request it
     *   from ApiSDK and cache it (see getFreshDataWithCache())
     * - If the item IS NOT in the cache and SHOULD NOT be cached, we simply
     *   query the ApiSDK and return without caching.
     *
     * @return mixed
     */
    public function get(string $id, string $type)
    {
        if ($this->shouldCacheEntity($id, $type)) {
            $key = $this->getKey($id, $type);
            $this->logger->debug('Fetching from cache', [
                'id' => $id,
                'type' => $type,
                'key' => $key,
            ]);
            if ($item = $this->cache->fetch($key)) {
                return $this->serializer->deserialize($item, Model::class, 'json');
            }
        }

        return $this->getFreshDataWithCache(new InternalSqsMessage($type, $id));
    }

    /**
     * Get fresh data with cache.
     *
     * This method call guarantees a fresh copy of them entity in ApiSDK represented
     * by the QueueItem. If the item should be cached, it will be cached at this point.
     *
     * @return mixed
     */
    private function getFreshDataWithCache(QueueItem $item)
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
                $this->serializer->serialize($entity, 'json'),
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

    private function getKeyFromQueueItem(QueueItem $item) : string
    {
        return static::getKey($item->getType(), $item->getId());
    }

    public static function getKey(string $type, string $id) : string
    {
        return sha1("$id:-:$type");
    }

    public function transform(QueueItem $item, bool $serialized = true)
    {
        $entity = $this->getFreshDataWithCache($item);
        if ($serialized === false) {
            return $entity;
        }

        return $this->serializer->serialize($entity, 'json');
    }
}
