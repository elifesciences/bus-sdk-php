<?php

namespace eLife\Bus\Queue;

use eLife\ApiSdk\ApiSdk;
use JMS\Serializer\Serializer;
use LogicException;

trait BasicTransformer
{
    /** @var ApiSdk */
    private $sdk;
    /** @var Serializer */
    private $serializer;

    public function getSdk(QueueItem $item)
    {
        switch ($item->getType()) {
            case 'blog-article':
                return $this->sdk->blogArticles();
                break;

            case 'event':
                return $this->sdk->events();
                break;

            case 'interview':
                return $this->sdk->interviews();
                break;

            case 'labs-experiment':
                return $this->sdk->labsExperiments();
                break;

            case 'podcast-episode':
                return $this->sdk->podcastEpisodes();
                break;

            case 'collection':
                return $this->sdk->collections();
                break;

            case 'article':
                return $this->sdk->articles();
                break;

            default:
                throw new LogicException("ApiSDK does not exist for the type `{$item->getType()}`.");
        }
    }

    public function transform(QueueItem $item, bool $serialized = true)
    {
        $sdk = $this->getSdk($item);
        $entity = $sdk->get($item->getId())->wait(true);
        if ($serialized === false) {
            return $entity;
        }

        return $this->serializer->serialize($entity, 'json');
    }
}
