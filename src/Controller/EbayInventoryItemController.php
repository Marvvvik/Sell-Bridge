<?php

namespace App\Controller;

use App\Service\EbayInventoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface; 

class EbayInventoryItemController extends AbstractController
{
    private $ebayInventoryService;
    private $logger; 

    public function __construct(EbayInventoryService $ebayInventoryService, LoggerInterface $logger)
    {
        $this->ebayInventoryService = $ebayInventoryService;
        $this->logger = $logger; 
    }

    /**
     * Маршрут для добавления товара в инвентарь eBay через POST запрос
     */
    #[Route('/ebay/inventory/add', name: 'add_ebay_inventory_item', methods: ['POST'])]
    public function addItem(Request $request): Response
    {
        $error = $this->ebayInventoryService->addItemFromRequest($request);

        if ($error) {
            return new Response('Failed to add data: ' . $error, Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Item successfully added to eBay inventory');
        return new Response('Item successfully added to eBay inventory', Response::HTTP_CREATED); 
    }

    /**
     * Маршрут для получения всех товаров из инвентаря eBay через GET запрос
     */
    #[Route('/ebay/inventory', name: 'get_ebay_inventory_items', methods: ['GET'])]
    public function getItems(): Response
    {
        // Получаем список товаров из инвентаря через сервис
        $items = $this->ebayInventoryService->getItems(); 
        
        // Возвращаем список товаров в формате JSON
        return $this->json($items);
    }

    /**
     * Маршрут для получения информации о товаре по его SKU через GET запрос
     */
    #[Route('/ebay/inventory/{sku}', name: 'get_ebay_inventory_item_by_sku', methods: ['GET'])]
    public function getItem(string $sku): Response
    {
        $item = $this->ebayInventoryService->getItem($sku); 

        // Если товар не найден, возвращаем ошибку 404
        if (!$item) {
            $this->logger->warning('Item not found with the given SKU', ['sku' => $sku]);
        }

        $this->logger->info('Item found', ['sku' => $sku]); 
        return $this->json($item); 
    }

    /**
     * Маршрут для удаления товара из инвентаря eBay по SKU через DELETE запрос
     */
    #[Route('/ebay/inventory/delete/{sku}', name: 'delete_ebay_inventory_item', methods: ['DELETE'])]
    public function deleteItem(string $sku): Response
    {
        $this->ebayInventoryService->deleteItem($sku); 
        $this->logger->info('Item successfully deleted from eBay inventory', ['sku' => $sku]);
        return new Response('Item successfully deleted from eBay inventory', Response::HTTP_OK); 
    }
}
