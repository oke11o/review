<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\SomeServiceClient;

class SomeServiceClientDecorator extends SomeServiceClient
{
    private $cache = null;
    private $logger = null;
    private $cacheLifetime = '+1 day';

    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setLifetime(string $lifetime)
    {
        $this->cacheLifetime = $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $request): array
    {
        if (is_null($this->logger)) {
            throw new Exception('Logger object is not defined.');
        }

        if (is_null($this->cache)) {
            throw new Exception('Cache object is not defined.');
        }

        try {
            $cacheKey = $this->getCacheKey($request);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($request);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify($this->cacheLifetime)
                );

            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error getting data from Some API');

            return ['error' => 500, 'message' => 'Unknown error'];
        }
    }

    private function getCacheKey(array $request)
    {
        return json_encode($request);
    }
}
