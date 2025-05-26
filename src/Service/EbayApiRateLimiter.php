<?php 

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class EbayApiRateLimiter
{
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private int $limit;

    public function __construct(CacheInterface $cache, LoggerInterface $logger, int $limit = 2_000_000)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->limit = $limit;
    }

    /**
     * Увеличивает счетчик вызовов API за текущий день и проверяет, не превышен ли лимит.
     *
     * @return bool True, если вызов разрешен (лимит не превышен), false в противном случае.
     */
    public function incrementAndCheck(): bool
    {
        $key = 'ebay_inventory_api_call_count_' . date('Ymd');

        $count = $this->cache->get($key, fn() => 0);

        if ($count >= $this->limit) {
            $this->logger->warning("Daily eBay API call limit reached: {$count}");
            return false;
        }

        $this->cache->delete($key);
        $this->cache->get($key, fn() => $count + 1);

        return true;
    }
}