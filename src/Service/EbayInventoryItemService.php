<?php

namespace App\Service;

use App\Config\EbayApiEndpoints;
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
    private string $baseUrl;
    private EbayApiRateLimiter $rateLimiter;

    public function __construct(
        Client $client = null,
        TokenService $tokenService,
        LoggerInterface $logger,
        InventoryItemRepository $inventoryItemRepository,
        ParameterBagInterface $params,
        EbayApiRateLimiter $rateLimiter,
    )
    {
        $this->client = $client ?? new Client();
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->rateLimiter = $rateLimiter;
        $environment = $params->get('ebay_environment');
        $this->baseUrl = EbayApiEndpoints::getBaseApiUrl($environment);
    }
    
    /**
     * Добавление товара на eBay на основе записи из базы данных.
     *
     * @param string $sku SKU товара для добавления.
     * @return array|null Массив с данными добавленного товара или null в случае ошибки.
     */
    public function addItemFromDatabaseItem(string $sku): ?array
    {
        try {
            $item = $this->inventoryItemRepository->findBySku($sku);
            
            if (!$item) {
                $this->logger->warning("Item not found in database with SKU: {$sku}");
                return null;
            }

            // Получаем Token из метода
            $accessToken = $this->getAccessToken();

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                $this->logger->warning("eBay API call not executed: daily limit has been exceeded.");
                return null;
            }

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                return null; 
            }

            // Формируем URL для создания товара на eBay
            $url = $this->baseUrl . "/sell/inventory/v1/inventory_item/{$sku}";
            // Формируем body для создания товара на eBay
            $itemData = $this->buildItemData($item);

            // Отправляем POST запрос для создания добавления товара в инфентарь
            $response = $this->client->put($url, [
                'headers' => $this->buildJsonHeaders($accessToken),
                'json' => $itemData,
            ]);

            $status = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $this->logger->info("Response from eBay: {$responseBody}");

            return in_array($status, [200, 204]) ? $itemData : json_decode($responseBody, true) ?? null;
        } catch (\Throwable $e) {
            $this->logger->error("Error adding item to eBay: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение списка всех товаров из eBay.
     *
     * @param int $limit Максимальное количество возвращаемых товаров за один запрос.
     * @param int $offset Смещение для пагинации результатов.
     * @return array|null Массив с информацией о товарах или null в случае ошибки.
     */
    public function getAllItems(int $limit = 10, int $offset = 0): ?array
    {
        try {
            // Получаем Token из метода
            $accessToken = $this->getAccessToken();

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                $this->logger->warning("eBay API call not executed: daily limit has been exceeded.");
                return null;
            }

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                return null;
            }

            // Формируем URL для запроса предметов
            $url = $this->baseUrl . "/sell/inventory/v1/inventory_item?limit={$limit}&offset={$offset}";

            // Отправляем GET запрос для получения товаров
            $response = $this->client->get($url, [
                'headers' => $this->buildAuthHeaders($accessToken),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            $this->logger->error("Error fetching inventory list: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение информации о товаре по его SKU.
     *
     * @param string $sku SKU товара.
     * @return array|null Массив с информацией о товаре или null в случае ошибки.
     */
    public function getItemBySku(string $sku): ?array
    {
        try {
            // Получаем Token из метода
            $accessToken = $this->getAccessToken();

            // Если Token не найден, логируем предупреждение и возвращаем null
            if (!$accessToken) {
                $this->logger->warning("No access token retrieved");
                return null;
            }

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                $this->logger->warning("eBay API call not executed: daily limit has been exceeded.");
                return null;
            }

            // Формируем URL для запроса предмета по SKU
            $url = $this->baseUrl . "/sell/inventory/v1/inventory_item/{$sku}";

             // Отправляем GET запрос для получения предмета по SKU
            $response = $this->client->get($url, [
                'headers' => $this->buildAuthHeaders($accessToken),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            $this->logger->error("Error fetching item by SKU: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Удаление товара с eBay по его SKU.
     *
     * @param string $sku SKU товара для удаления.
     * @return bool True в случае успешного удаления, false в случае ошибки.
     */
    public function deleteItemBySku(string $sku): bool
    {
        try {
            // Получаем Token из метода
            $accessToken = $this->getAccessToken();

            // Если Token не найден, логируем предупреждение и возвращаем null
            if (!$accessToken) {
                $this->logger->warning("No access token retrieved");
                return null;
            }

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                $this->logger->warning("eBay API call not executed: daily limit has been exceeded.");
                return null;
            }

            // Формируем URL для удаления предмета по SKU
            $url = $this->baseUrl  . "/sell/inventory/v1/inventory_item/{$sku}";

            // Отправляем DELET запрос для удаления предмета по SKU
            $response = $this->client->delete($url, [
                'headers' => $this->buildAuthHeaders($accessToken),
            ]);

            return $response->getStatusCode() === 204;
        } catch (\Throwable $e) {
            $this->logger->error("Error deleting item from eBay: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получает действительный Access Token.
     *
     * @return string|null Access Token или null, если получить не удалось.
     */
    private function getAccessToken(): ?string
    {
        $accessToken = $this->tokenService->getValidAccessToken();

        if (!$accessToken) {
            $this->logger->warning("No access token retrieved");
        }

        return $accessToken;
    }

    /**
     * Метод для для создания Auth Header
     * 
     * @param string $accessToken Действительный Access Token.
     * @return array Массив с заголовком 'Authorization'.
     */
    private function buildAuthHeaders(string $accessToken): array
    {
        return [
            'Authorization' => "Bearer {$accessToken}",
            'Accept' => 'application/json',
        ];
    }

    /**
     * Метод для для создания Build Header
     * 
     * @param string $accessToken Действительный Access Token.
     * @return array Массив с заголовками 'Authorization', 'Accept' и 'Content-Type'.
     */
    private function buildJsonHeaders(string $accessToken): array
    {
        return[
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US',
        ];
    }

    /**
     * Формирование массива itemData из сущности
     *
     * @param InventoryItem $item Сущность товара из базы данных.
     * @return array Массив с данными товара в формате, ожидаемом API eBay.
     */
    private function buildItemData(InventoryItem $item): array
    {
        return [
            'sku' => $item->getSku(),
            'locale' => $item->getLocale(),
            'product' => [
                'title' => $item->getTitle(),
                'brand' => $item->getBrand(),
                'mpn' => $item->getMPN(),
                'aspects' => [
                    'Size' => [$item->getSize()],
                    'Color' => [$item->getColor()],
                    'Material' => [$item->getMaterial()],
                ],
                'description' => $item->getDescription(),
                'imageUrls' => $item->getImageUrls(),
            ],
            'condition' => $item->getItemCondition(),
        ];
    }
}
