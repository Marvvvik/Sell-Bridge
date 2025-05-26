<?php

namespace App\Controller;

use App\Service\EbayInventoryItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class EbayInventoryApiController extends AbstractController
{
    private $ebayInventoryItemService;
    private $logger;

    public function __construct(EbayInventoryItemService $ebayInventoryItemService, LoggerInterface $logger)
    {
        $this->ebayInventoryItemService = $ebayInventoryItemService;
        $this->logger = $logger;
    }

    /**
     * Маршрут для добавления товара на eBay по SKU
     */
    #[Route('/ebay/inventoryItem/add/{sku}', name: 'add_ebay_inventory_api_item', methods: ['PUT'])]
    public function addItem(string $sku): Response
    {
        // Вызываем метод добавления товара из сервиса
        $result = $this->ebayInventoryItemService->addItemFromDatabaseItem($sku);
    
        // Если товар успешно добавлен, логируем это и возвращаем ответ с успешным статусом
        if ($result) {
            $this->logger->info('Item successfully added to eBay with SKU: ' . $sku);
            return $this->json(['message' => 'Item added to eBay inventory successfully.', 'data' => $result], 200);
        }
    
         // Если добавление товара не удалось, логируем это и возвращаем ошибку
        $this->logger->error('Failed to add item to eBay inventory with SKU: ' . $sku);
        return $this->json(['message' => 'Failed to add item to eBay inventory.'], 400);
    }

    /**
     * Маршрут для получения всех товаров из инвентаря eBay
     */
    #[Route('/ebay/inventoryItem/items', name: 'get_all_ebay_inventory_api_items', methods: ['GET'])]
    public function getAllItems(): JsonResponse
    {
        // Можно передавать limit и offset как параметры
        $result = $this->ebayInventoryItemService->getAllItems(10, 0);

        if ($result) {
            $this->logger->info('Inventory items retrieved successfully.');
            return $this->json(['message' => 'Inventory items retrieved successfully.','data' => $result]); 
        }
        $this->logger->error('Failed to retrieve inventory items.' . $sku); 
        return $this->json(['message' => 'Failed to retrieve inventory items.'], 400); 
    }

    /**
     * Маршрут для получения товара по SKU
     */
    #[Route('/ebay/inventoryItem/item/{sku}', name: 'get_ebay_inventory_api_by_sku', methods: ['GET'])]
    public function getItemBySku(string $sku): JsonResponse
    {
        $result = $this->ebayInventoryItemService->getItemBySku($sku); 

        if ($result) {
            $this->logger->info('Item retrieved successfully.');
            return $this->json(['message' => 'Item retrieved successfully.','data' => $result]); 
        }
        $this->logger->error('Failed to retrieve item with SKU' . $sku);
        return $this->json(['message' => 'Failed to retrieve item with SKU: ' . $sku], 404); 
    }

    /**
     * Маршрут для удаления товара по SKU
     */
    #[Route('/ebay/inventoryItem/delete/{sku}', name: 'delete_ebay_inventory_api_item', methods: ['DELETE'])]
    public function deleteItem(string $sku): JsonResponse
    {
        $result = $this->ebayInventoryItemService->deleteItemBySku($sku); 

        if ($result) {
            $this->logger->info('Item with SKU' . $sku . 'successfully deleted from eBay inventory.');
            return $this->json(['message' => "Item with SKU $sku successfully deleted from eBay inventory."], 200);
        }

        $this->logger->error('Failed to delete item with SKU' . $sku . 'from eBay inventory.'); 
        return $this->json(['message' => "Failed to delete item with SKU $sku from eBay inventory."], 400);
    }
}
