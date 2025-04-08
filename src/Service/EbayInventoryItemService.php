<?php

namespace App\Service;

use App\Entity\InventoryItem;
use App\Repository\InventoryItemRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class EbayInventoryItemService
{
    private $client;
    private $logger;
    private $tokenService;
    private $inventoryItemRepository; 
    private string $Environment;

    public function __construct(Client $client, TokenService $tokenService, LoggerInterface $logger, InventoryItemRepository $inventoryItemRepository, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->Environment = $params->get('ebay_environment');
    }

    private function getApiUrl(string $endpoint): string // метод для получания сылки в каой среде работать
    {
        $baseUrl = match ($this->Environment) {
            'Production' => 'https://api.ebay.com',
            'Sandbox' => 'https://api.sandbox.ebay.com',
            default => throw new \InvalidArgumentException('Invalid environment specified.'),
        };

        return $baseUrl . $endpoint;
    }
    
    public function addItemFromDatabaseItem(string $sku): ?array   // Метод для добавления товара на eBay на основе данных из базы по SKU
    {
        try {
            $item = $this->inventoryItemRepository->findBySku($sku);  // Получаем товар из базы данных по SKU

            if (!$item) { // Если товар не найден в базе, логируем предупреждение и возвращаем null
                $this->logger->warning('Item not found in database with SKU: ' . $sku);
                return null;
            }
            
            $itemData = [  // Формируем данные товара, которые будут отправлены в API eBay
                'sku' => $item->getSku(),
                'locale' => $item->getLocale(),
                'product' => [
                    'title' => $item->getTitle(),
                    'brand' => $item->getBrand(),
                    'mpn' => $item->getMPN(),
                    'aspects' => [
                        'Size' => [$item->getSize()],
                        'Color' => [$item->getColor()],
                        'Material' => [$item->getMaterial()]
                    ],
                    'description' => $item->getDescription(),
                    'imageUrls' => $item->getImageUrls(),
                ],
                'condition' => $item->getItemCondition(),
            ];

            // $this->logger->info('Preparing to add item to eBay: ' . json_encode($itemData)); // Логируем подготовленные данные, это можно добавить для отладки

            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved for SKU: ' . $item->getSku());
            } else {
                $this->logger->warning('No access token retrieved for SKU: ' . $item->getSku());
            }

            $url = $this->getApiUrl('/sell/inventory/v1/inventory_item/' . $sku); // Формируем правильный URL для API запроса

            //  $this->logger->info('URL: ' . $url); //вывод url так же для отладки 

            $response = $this->client->put($url, [ // Отправляем PUT запрос для добавления товара
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Content-Language' => 'en-US',
                ],
                'json' => $itemData,
            ]);

           
            $responseBody = $response->getBody()->getContents(); // Получаем содержимое ответа от eBay
            $this->logger->info('Response from eBay: ' . $responseBody);  // Логируем ответ от eBay тоже для отладки

           
            if ($response->getStatusCode() === 204 || $response->getStatusCode() === 200) { // Проверяем код ответа и возвращаем данные, если всё в порядке
                return $itemData;  
            }

            $responseData = json_decode($responseBody, true); // Если ответ содержит ошибки, декодируем и возвращаем их
            return $responseData ? $responseData : null; // Если ответ содержит данные, обрабатываем их

        } catch (\Exception $e) {
            $this->logger->error('Error adding item to eBay inventory - ' . $e->getMessage());
            return null;
        }
    }

    public function getAllItems(int $limit = 10, int $offset = 0): ?array // Метод для получения всех товаров с eBay
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved');
            } else {
                $this->logger->warning('No access token retrieved');
            }
            
            $url = $this->getApiUrl('/sell/inventory/v1/inventory_item?limit=' . $limit . '&offset=' . $offset); // Формируем URL для запроса списка товаров с eBay с учетом лимита и смещения

            $response = $this->client->get($url, [ // Отправляем GET запрос для получения списка товаров
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();  // Получаем содержимое ответа от eBay
            //$this->logger->info('Fetched inventory list: ' . $responseBody); // Логируем ответ от eBay тоже для отладки

            return json_decode($responseBody, true); // Декодируем и возвращаем данные из ответа
        } catch (\Exception $e) {
            $this->logger->error('Error fetching inventory list - ' . $e->getMessage());
            return null;
        }
    }

    public function getItemBySku(string $sku): ?array // Метод для получения товара по SKU с eBay
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken();   // Получаем токен доступа для eBay
            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved');
            } else {
                $this->logger->warning('No access token retrieved');
            }

            $url = $this->getApiUrl('/sell/inventory/v1/inventory_item/' . $sku); // Формируем URL для запроса товара по SKU

            $response = $this->client->get($url, [  // Отправляем GET запрос для получения товара по SKU
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);

            $responseBody = $response->getBody()->getContents(); // Получаем содержимое ответа от eBay
            // $this->logger->info('Fetched item with SKU ' . $sku . ': ' . $responseBody);// Логируем ответ от eBay тоже для отладки

            return json_decode($responseBody, true); // Декодируем и возвращаем данные из ответа
        } catch (\Exception $e) {
            $this->logger->error('Error fetching item by SKU - ' . $e->getMessage());
            return null;
        }
    }

    public function deleteItemBySku(string $sku): bool // Метод для удаления товара по SKU с eBay
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if (!$accessToken) {
                $this->logger->warning("Access token not found for SKU: $sku");
                return false;
            }

            $url = $this->getApiUrl('/sell/inventory/v1/inventory_item/' . $sku); // Формируем URL для запроса удаления товара по SKU

            $response = $this->client->delete($url, [ // Отправляем DELETE запрос для удаления товара
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 204) { // Проверяем код ответа и возвращаем true, если удаление прошло успешно
                return true;
            } else { // Логируем ошибку, если удаление не удалось
                $this->logger->error("Failed to delete item with SKU $sku. Status: " . $response->getStatusCode());
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error("Error deleting item from eBay inventory: " . $e->getMessage());
            return false;
        }
    }

}
