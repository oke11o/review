<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use src\Integration\ServiceDataProvider;

class DecoratorManager
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ServiceDataProvider
     */
    private $provider;
    private $expiresAt = '+1 day';

    public function __construct(ServiceDataProvider $provider, CacheItemPoolInterface $cache = null, LoggerInterface $logger = null, $expiresAt = '+1 day')
    {
        $this->provider = $provider;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->expiresAt = $expiresAt;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getResponseMy(array $input)
    {
        $result = $this->findInCache($input);
        if ($result) {
            return $result;
        }

        $result = $this->provider->get($input);

        $this->saveToCache($input, $result);

        return $result;
    }

    public function getResponse(array $input)
    {
        $cacheItem = $this->findInCache2($input);
        if ($cacheItem && $cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $result = $this->provider->get($input);

        $this->saveToCache2($cacheItem, $result);

        return $result;
    }

    public function getCacheKey(array $input)
    {
        return md5(json_encode($input));
    }

    private function findInCache(array $input): array
    {
        $result = [];
        if (!$this->cache) {
            return $result;
        }
        $cacheKey = $this->getCacheKey($input);
        try {
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $result = $cacheItem->get();
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->critical('Error TODO'); //TODO
        }

        return $result;
    }

    private function saveToCache(array $input, array $result)
    {
        if (!$this->cache) {
            return;
        }

        $cacheKey = $this->getCacheKey($input);
        try {
            $cacheItem = $this->cache->getItem($cacheKey);
        } catch (InvalidArgumentException $e) {
            $this->logger->critical('Error TODO'); //TODO
            return;
        }

        $cacheItem
            ->set($result)
            ->expiresAt(
                (new DateTime())->modify($this->expiresAt)
            );
    }

    /**
     * @param array $input
     * @return CacheItemInterface|null
     */
    protected function findInCache2(array $input): ?CacheItemInterface
    {
        $cacheKey = $this->getCacheKey($input);
        $cacheItem = null;
        try {
            $cacheItem = $this->cache->getItem($cacheKey);
        } catch (InvalidArgumentException $e) {
            $this->logger->critical('Error');
        }
        return $cacheItem;
    }

    /**
     * @param CacheItemInterface|null $cacheItem
     * @param array $result
     */
    protected function saveToCache2(?CacheItemInterface $cacheItem, array $result): void
    {
        if ($cacheItem) {
            try {
                $cacheItem
                    ->set($result)
                    ->expiresAt(
                        (new DateTime())->modify($this->expiresAt)
                    );
            } catch (Exception $e) {
                $this->logger->critical('Error');
            }
        }
    }
}
