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

    #[Route('/ebay/inventory/add', name: 'add_ebay_inventory_item', methods: ['POST'])]// Маршрут для добавления товара в инвентарь eBay через POST запрос
    public function addItem(Request $request): Response
    {
        $error = $this->ebayInventoryService->addItemFromRequest($request);

        if ($error) {
            return new Response('Failed to add data: ' . $error, Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Item successfully added to eBay inventory');
        return new Response('Item successfully added to eBay inventory', Response::HTTP_CREATED); 
    }

    #[Route('/ebay/inventory', name: 'get_ebay_inventory_items', methods: ['GET'])]   // Маршрут для получения всех товаров из инвентаря eBay через GET запрос
    public function getItems(): Response
    {
        $items = $this->ebayInventoryService->getItems(); // Получаем список товаров из инвентаря через сервис
        return $this->json($items); // Возвращаем список товаров в формате JSON
    }

    #[Route('/ebay/inventory/{sku}', name: 'get_ebay_inventory_item_by_sku', methods: ['GET'])] // Маршрут для получения информации о товаре по его SKU через GET запрос
    public function getItem(string $sku): Response
    {
        $item = $this->ebayInventoryService->getItem($sku); 

        if (!$item) { // Если товар не найден, возвращаем ошибку 404
            $this->logger->warning('Item not found with the given SKU', ['sku' => $sku]);
        }

        $this->logger->info('Item found', ['sku' => $sku]); 
        return $this->json($item); 
    }

    #[Route('/ebay/inventory/delete/{sku}', name: 'delete_ebay_inventory_item', methods: ['DELETE'])] // Маршрут для удаления товара из инвентаря eBay по SKU через DELETE запрос
    public function deleteItem(string $sku): Response
    {
        $this->ebayInventoryService->deleteItem($sku); 
        $this->logger->info('Item successfully deleted from eBay inventory', ['sku' => $sku]);
        return new Response('Item successfully deleted from eBay inventory', Response::HTTP_OK); 
    }
}
