<?php

namespace App\Tests\Service;

use App\Service\EbayInventoryItemService;
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

class EbayInventoryItemServiceTest extends TestCase
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

        $this->service = new EbayInventoryItemService(
            $this->client,
            $this->tokenService,
            $this->logger,
            $this->inventoryItemRepository,
            $this->params,
            $this->rateLimiter 
        );
    }

    /**
     * Тестирует получение всех товаров из eBay API и проверяет, что возвращается массив с ключом 'items'.
     */
    public function testGetAllItemsReturnsArray(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('abc123xyz');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 

        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn(json_encode(['items' => []]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('getStatusCode')->willReturn(200);

        $this->client->method('get')->willReturn($response);

        $result = $this->service->getAllItems();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
    }

    /**
     * Тестирует получение товара по SKU из eBay API.
     */
    public function testGetItemBySkuReturnsItem(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('abc123xyz');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 

        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn(json_encode(['sku' => 'ABC123']));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('getStatusCode')->willReturn(200);

        $this->client->method('get')->willReturn($response);

        $result = $this->service->getItemBySku('ABC123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sku', $result);
    }

    /**
     * Тестирует удаление товара по SKU и проверяет, что при успешном удалении возвращается true (код ответа 204).
     */
    public function testDeleteItemBySkuReturnsTrueOnSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('abc123xyz');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true); 

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);

        $this->client->method('delete')->willReturn($response);

        $result = $this->service->deleteItemBySku('ABC123');

        $this->assertTrue($result);
    }

    /**
     * Тестирует добавление товара из базы данных, когда товар с указанным SKU не найден в БД.
     * Ожидается, что метод вернет null.
     */
    public function testAddItemFromDatabaseItemWithMissingItem(): void
    {
        $this->inventoryItemRepository->method('findBySku')->willReturn(null);
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true);

        $result = $this->service->addItemFromDatabaseItem('NOT_FOUND_SKU');

        $this->assertNull($result);
    }

    /**
     * Тестирует успешное добавление товара из объекта InventoryItem в eBay.
     */
    public function testAddItemFromDatabaseItemSuccess(): void
    {
        $this->tokenService->method('getValidAccessToken')->willReturn('test_token');
        $this->rateLimiter->method('incrementAndCheck')->willReturn(true, true);

        $inventoryItem = new InventoryItem();
        $inventoryItem->setSku('TEST_SKU')
            ->setLocale('en_US')
            ->setTitle('Test Item')
            ->setBrand('Test Brand')
            ->setMPN('TB123')
            ->setSize('M')
            ->setColor('Blue')
            ->setMaterial('Cotton')
            ->setDescription('A test item.')
            ->setImageUrls(['http://example.com/image.jpg'])
            ->setItemCondition('NEW');

        $this->inventoryItemRepository->method('findBySku')->with('TEST_SKU')->willReturn($inventoryItem);

        $responseBody = json_encode(['sku' => 'TEST_SKU']);
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($responseBody);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->client->method('put')->willReturn($response);

        $result = $this->service->addItemFromDatabaseItem('TEST_SKU');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('TEST_SKU', $result['sku']);
    }
}