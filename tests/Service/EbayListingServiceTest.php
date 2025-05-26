<?php

namespace App\Tests\Service;

use App\Entity\EbayListing;
use App\Entity\InventoryItem;
use App\Repository\EbayListingRepository;
use App\Service\EbayListingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EbayListingServiceTest extends TestCase
{
    private $repository;
    private $entityManager;
    private $service;

    /**
     * Настройка окружения для тестов. Выполняется перед каждым тестовым методом.
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(EbayListingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new EbayListingService($this->repository, $this->entityManager);
    }

    /**
     * Тестирует метод получения всех листингов и их форматирование для отображения.
     */
    public function testGetAllListingsFormatted()
    {
        $inventoryItemMock = $this->createMock(InventoryItem::class);
        $listing = $this->createMock(EbayListing::class);
        $listing->method('getId')->willReturn(1);
        $listing->method('getSku')->willReturn('SKU123');
        $listing->method('getOfferId')->willReturn('OFFER1');
        $listing->method('getStatus')->willReturn('Active');
        $listing->method('getMarketplaceId')->willReturn('MARKET1');
        $listing->method('getStartTime')->willReturn(new \DateTime('2023-01-01 10:00:00'));
        $listing->method('getEndTime')->willReturn(new \DateTime('2023-01-02 10:00:00'));
        $listing->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-01-01 11:00:00'));
        $listing->method('getUpdatedAt')->willReturn(new \DateTime('2023-01-01 11:00:00'));
        $listing->method('getInventoryItem')->willReturn($inventoryItemMock);

        $this->repository->method('findAll')->willReturn([$listing]);

        $result = $this->service->getAllListingsFormatted();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('SKU123', $result[0]['SKU']);
    }

    /**
     * Тестирует сохранение листинга eBay, когда листинг с таким SKU еще не существует.
     * Ожидается создание нового объекта EbayListing и вызов метода 'save' репозитория.
     */
    public function testSaveEbayListingCreatesNew()
    {
        $this->repository->method('findOneBySku')->willReturn(null);

        $inventoryItem = $this->createMock(InventoryItem::class);
        $ebayResponse = [
            'offerId' => 'OFFER123',
            'status' => 'PUBLISHED',
            'startTime' => '2023-01-01T10:00:00Z',
            'endTime' => '2023-01-02T10:00:00Z',
            'marketplaceId' => 'EBAY_US'
        ];

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EbayListing::class), true);

        $result = $this->service->saveEbayListing($inventoryItem, 'SKU999', $ebayResponse);

        $this->assertInstanceOf(EbayListing::class, $result);
        $this->assertEquals('OFFER123', $result->getOfferId());
    }

    /**
     * Тестирует сохранение листинга eBay, когда листинг с таким SKU уже существует.
     * Ожидается обновление существующего объекта EbayListing.
     */
    public function testSaveEbayListingUpdatesExisting()
    {
        $existingListing = new EbayListing();
        $existingListing->setSku('SKUEXIST');
        $existingListing->setCreatedAt(new \DateTimeImmutable());

        $this->repository->method('findOneBySku')->willReturn($existingListing);

        $inventoryItem = $this->createMock(InventoryItem::class);
        $ebayResponse = [
            'offerId' => 'OFFER456',
            'status' => 'ENDED'
        ];

        $result = $this->service->saveEbayListing($inventoryItem, 'SKUEXIST', $ebayResponse);

        $this->assertEquals('OFFER456', $result->getOfferId());
    }

    /**
     * Тестирует успешное удаление листинга по offerId.
     * Ожидается вызов методов 'findOneBy', 'remove' и 'flush' EntityManager.
     */
    public function testDeleteByOfferIdSuccess()
    {
        $listing = $this->createMock(EbayListing::class);
        $this->repository->method('findOneBy')->with(['offerId' => 'TO_DELETE'])->willReturn($listing);

        $this->entityManager->expects($this->once())->method('remove')->with($listing);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->service->deleteByOfferId('TO_DELETE'));
    }

    /**
     * Тестирует удаление листинга по offerId, когда листинг не найден.
     * Ожидается, что методы 'remove' и 'flush' EntityManager не будут вызваны.
     */
    public function testDeleteByOfferIdNotFound()
    {
        $this->repository->method('findOneBy')->willReturn(null);
        $this->assertFalse($this->service->deleteByOfferId('UNKNOWN'));
    }
}