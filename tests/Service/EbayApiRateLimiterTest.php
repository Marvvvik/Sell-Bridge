<?php

use App\Service\EbayApiRateLimiter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class EbayApiRateLimiterTest extends TestCase
{
    /**
     * Тестирует ситуацию, когда количество API-вызовов не превышает лимит.
     */
    public function testAllowsApiCallUnderLimit()
    {
        $cache = $this->createMock(CacheInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $cache->method('get')->willReturnCallback(function ($key, $callback) {
            return $callback($this->createMock(ItemInterface::class));
        });

        $rateLimiter = new EbayApiRateLimiter($cache, $logger);
        $this->assertTrue($rateLimiter->incrementAndCheck());
    }

    /**
     * Тестирует ситуацию, когда достигнут лимит API-вызовов.
     */
    public function testDeniesApiCallWhenLimitReached()
    {
        $cache = $this->createMock(CacheInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $cache->method('get')->willReturn(2_000_000); 

        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Daily eBay API call limit reached'));

        $rateLimiter = new EbayApiRateLimiter($cache, $logger);
        $this->assertFalse($rateLimiter->incrementAndCheck());
    }
}
