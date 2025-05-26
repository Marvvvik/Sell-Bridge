<?php

namespace App\Tests\Service;

use App\Service\EbayOffersService;
use App\Service\TokenService;
use App\Repository\InventoryItemRepository;
use App\Service\EbayApiRateLimiter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use App\Entity\InventoryItem;

class EbayOffersServiceTest extends TestCase
{
    private $client;
    private $tokenService;
    private $logger;
    private $inventoryItemRepository;
    private $params;
    private $rateLimiter;
    private $service;

    /**
     * Настройка окружения для тестов. Выполняется перед каждым тестовым методом.
     */
    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->tokenService = $this->createMock(TokenService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->inventoryItemRepository = $this->createMock(InventoryItemRepository::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->rateLimiter = $this->createMock(EbayApiRateLimiter::class); 
        $this->params->method('get')->with('ebay_environment')->willReturn('Sandbox');

        $this->service = new EbayOffersService(
            $this->client,
            $this->tokenService,
            $this->logger,
            $this->inventoryItemRepository,
            $this->params,
            $this->rateLimiter
        );
    }

    /**
     * Тестирует успешное создание предложения (Offer) на eBay на основе товара из базы данных.
     */
    public function testCreateOfferFromDatabaseItemSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $inventoryItem = $this->createMockInventoryItem();
        $this->inventoryItemRepository->method('findBySku')->with('TEST_SKU')->willReturn($inventoryItem);
        $responseBody = json_encode(['offerId' => '12345']);
        $response = $this->createMockHttpResponse(200, $responseBody);
        $this->client->method('post')->willReturn($response);

        $result = $this->service->createOfferFromDatabaseItem('TEST_SKU');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('offerId', $result);
        $this->assertEquals('12345', $result['offerId']);
    }

    /**
     * Тестирует создание предложения при отсутствии товара с указанным SKU в базе данных.
     * Ожидается, что метод вернет null.
     */
    public function testCreateOfferFromDatabaseItemWithMissingItem(): void
    {
        $this->inventoryItemRepository->method('findBySku')->willReturn(null);
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $result = $this->service->createOfferFromDatabaseItem('NOT_FOUND_SKU');
        $this->assertNull($result);
    }

    /**
     * Тестирует успешное получение списка предложений для определенного SKU.
     */
    public function testGetOffersReturnsArrayOnSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $responseBody = json_encode(['offers' => [['offerId' => 'OFFER1']]]);
        $response = $this->createMockHttpResponse(200, $responseBody);
        $this->client->method('get')->willReturn($response);

        $result = $this->service->getOffers('TEST_SKU');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('offers', $result);
        $this->assertCount(1, $result['offers']);
        $this->assertArrayHasKey('offerId', $result['offers'][0]);
        $this->assertEquals('OFFER1', $result['offers'][0]['offerId']);
    }

    /**
     * Тестирует ситуацию, когда при получении предложений с eBay API возвращается ошибка.
     */
    public function testGetOffersReturnsNullOnError(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $response = $this->createMockHttpResponse(500, json_encode(['error' => 'Internal Server Error']));
        $this->client->method('get')->willReturn($response);

        $result = $this->service->getOffers('TEST_SKU');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Internal Server Error', $result['error']);
    }

    /**
     * Тестирует успешное получение информации о предложении по его ID.
     */
    public function testGetOfferByOfferIdReturnsArrayOnSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $responseBody = json_encode(['offerId' => 'OFFER123', 'sku' => 'TEST_SKU']);
        $response = $this->createMockHttpResponse(200, $responseBody);
        $this->client->method('get')->willReturn($response);

        $result = $this->service->getOfferByOfferId('OFFER123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('offerId', $result);
        $this->assertEquals('OFFER123', $result['offerId']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('TEST_SKU', $result['sku']);
    }

    /**
     * Тестирует ситуацию, когда предложение с указанным ID не найдено на eBay.
     */
    public function testGetOfferByOfferIdReturnsNullOnError(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $response = $this->createMockHttpResponse(404, json_encode(['error' => 'Offer Not Found']));
        $this->client->method('get')->willReturn($response);

        $result = $this->service->getOfferByOfferId('NON_EXISTENT_OFFER');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Offer Not Found', $result['error']);
    }

    /**
     * Тестирует успешное удаление предложения по его ID.
     */
    public function testDeleteOfferByOfferIdReturnsTrueOnSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $response = $this->createMockHttpResponse(204, '');
        $this->client->method('delete')->willReturn($response);

        $result = $this->service->deleteOfferByOfferId('OFFER_TO_DELETE');

        $this->assertTrue($result);
    }

    /**
     * Тестирует неудачное удаление предложения (например, если предложение не найдено).
     */
    public function testDeleteOfferByOfferIdReturnsFalseOnError(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $response = $this->createMockHttpResponse(404, json_encode(['error' => 'Offer Not Found']));
        $this->client->method('delete')->willReturn($response);

        $result = $this->service->deleteOfferByOfferId('NON_EXISTENT_OFFER');

        $this->assertFalse($result);
    }

    /**
     * Тестирует успешную публикацию предложения.
     */
    public function testPublishOfferReturnsArrayOnSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $responseBody = json_encode(['listingId' => 'EBAY12345']);
        $response = $this->createMockHttpResponse(200, $responseBody);
        $this->client->method('post')->willReturn($response);

        $result = $this->service->publishOffer('OFFER_TO_PUBLISH');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('listingId', $result);
        $this->assertEquals('EBAY12345', $result['listingId']);
    }

    /**
     * Тестирует неудачную попытку публикации предложения (например, неверный статус предложения).
     */
    public function testPublishOfferReturnsNullOnError(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 
        $response = $this->createMockHttpResponse(400, json_encode(['error' => 'Invalid Offer State']));
        $this->client->method('post')->willReturn($response);

        $result = $this->service->publishOffer('OFFER_TO_PUBLISH');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid Offer State', $result['error']);
    }

    /**
     * Вспомогательный метод для создания мок-объекта ResponseInterface.
     */
    private function createMockHttpResponse(int $statusCode, string $body): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }

    /**
     * Вспомогательный метод для создания мок-объекта InventoryItem с минимально необходимыми полями.
     */
    private function createMockInventoryItem(): InventoryItem
    {
        $inventoryItem = new InventoryItem();
        $inventoryItem->setSku('TEST_SKU')
            ->setMarketplaceId('EBAY_US')
            ->setFormat('FIXED_PRICE')
            ->setDescription('A test item for offer.')
            ->setAvailabilityQuantity(5)
            ->setPrice(25.99)
            ->setCurrency('USD');
        return $inventoryItem;
    }
}