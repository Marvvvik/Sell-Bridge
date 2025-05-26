<?php

namespace App\Tests\Service;

use App\Entity\InventoryItem;
use App\Repository\InventoryItemRepository;
use App\Service\EbayInventoryService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class EbayInventoryServiceTest extends TestCase
{
    private $repositoryMock;
    private $loggerMock;
    private EbayInventoryService $service;

    /**
     * Настройка окружения для тестов. Выполняется перед каждым тестовым методом.
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(InventoryItemRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->service = new EbayInventoryService($this->repositoryMock, $this->loggerMock);
    }

    /**
     * Тестирует добавление товара из запроса с отсутствующим обязательным полем ('locale').
     * Ожидается логирование ошибки и возврат сообщения об отсутствующем поле.
     */
    public function testAddItemFromRequestWithMissingField()
    {
        $data = ['sku' => 'ABC123']; 
        $request = new Request([], [], [], [], [], [], json_encode($data));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Missing field: locale'));

        $result = $this->service->addItemFromRequest($request);
        $this->assertStringContainsString('Missing field', $result);
    }

    /**
     * Тестирует успешное добавление товара из корректного запроса.
     * Ожидается, что будет вызван метод 'save' репозитория.
     */
    public function testAddItemFromRequestSuccess()
    {
        $data = [
            'sku' => 'SKU123',
            'locale' => 'en-US',
            'product' => [
                'title' => 'Test Product',
                'aspects' => [
                    'Size' => ['M'],
                    'Color' => ['Red'],
                    'Material' => ['Cotton'],
                ],
                'description' => 'A sample product',
                'brand' => 'TestBrand',
                'imageUrls' => ['http://image.jpg'],
                'mpn' => 'MPN123',
            ],
            'condition' => 'NEW',
            'price' => ['value' => 29.99, 'currency' => 'USD'],
            'quantity' => 5,
            'availability_quantity' => 10,
            'marketplaceId' => 'EBAY_US',
            'format' => 'FIXED_PRICE'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($data));

        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(InventoryItem::class), true);

        $result = $this->service->addItemFromRequest($request);
        $this->assertNull($result);
    }

    /**
     * Тестирует успешное добавление товара через прямой вызов метода 'addItem'.
     * Ожидается вызов метода 'save' репозитория.
     */
    public function testAddItemSuccess()
    {
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(InventoryItem::class), true);

        $result = $this->service->addItem(
            'SKU123', 'en-US', 'Title', 'L', 'Blue', 'Cotton', 'Description',
            'Nike', ['http://img.jpg'], 'NEW', 100.0, 'USD', 10, 5,
            new \DateTimeImmutable(), 'EBAY_US', 'FIXED_PRICE', 'MPN456'
        );

        $this->assertNull($result);
    }

    /**
     * Тестирует ситуацию, когда при добавлении товара возникает ошибка на уровне репозитория.
     * Ожидается логирование ошибки и возврат сообщения об ошибке.
     */
    public function testAddItemFailure()
    {
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error adding item to inventory'));

        $result = $this->service->addItem(
            'SKU123', 'en-US', 'Title', 'L', 'Blue', 'Cotton', 'Description',
            'Nike', ['http://img.jpg'], 'NEW', 100.0, 'USD', 10, 5,
            new \DateTimeImmutable(), 'EBAY_US', 'FIXED_PRICE', 'MPN456'
        );

        $this->assertEquals('Error adding item to inventory', $result);
    }

    /**
     * Тестирует получение всех товаров из инвентаря.
     * Ожидается вызов метода 'getAllItems' репозитория и возврат массива товаров.
     */
    public function testGetItems()
    {
        $this->repositoryMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([new InventoryItem()]);

        $items = $this->service->getItems();
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }

    /**
     * Тестирует получение товара по SKU.
     * Ожидается вызов метода 'findBySku' репозитория и возврат найденного товара.
     */
    public function testGetItem()
    {
        $expectedItem = $this->createMock(InventoryItem::class);

        $this->repositoryMock->expects($this->once())
            ->method('findBySku')
            ->with('SKU456')
            ->willReturn($expectedItem);

        $result = $this->service->getItem('SKU456');
        $this->assertSame($expectedItem, $result);
    }

    /**
     * Тестирует удаление товара по SKU.
     * Ожидается вызов метода 'deleteBySku' репозитория.
     */
    public function testDeleteItem()
    {
        $this->repositoryMock->expects($this->once())
            ->method('deleteBySku')
            ->with('SKU456', true);

        $this->service->deleteItem('SKU456');
        $this->assertTrue(true); 
    }
}
