<?php

namespace App\Service;

use App\Config\EbayApiEndpoints;
use App\Entity\EbayListing;
use App\Entity\InventoryItem;
use App\Repository\InventoryItemRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class EbayOffersService
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
    ) {
        $this->client = $client ?? new Client();
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->rateLimiter = $rateLimiter;
        $environment = $params->get('ebay_environment');
        $this->baseUrl = EbayApiEndpoints::getBaseApiUrl($environment);
    }

    /**
     * Метод для создания предложения товара на eBay на основе данных из базы по SKU
     *
     * @param string $sku SKU товара.
     * @return array|null Ответ от API eBay после создания предложения или null в случае ошибки.
     */
    public function createOfferFromDatabaseItem(string $sku): ?array 
    {
        try {
            // Получаем товар из базы данных по SKU
            $item = $this->inventoryItemRepository->findBySku($sku); 

            if (!$item) { 
                $this->logger->warning('Item not found in database with SKU: ' . $sku);
                return null;
            }

            // Получаем ACCESS TOKEN из метода
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                $this->logger->warning("No access token retrieved");
                return null;
            }

            // проверка лимита запросов
            if (!$this->rateLimiter->incrementAndCheck()) {
                $this->logger->warning("eBay API call not executed: daily limit has been exceeded.");
                return null;
            }

            // Формируем URL для запроса создания offer
            $url = $this->baseUrl . '/sell/inventory/v1/offer'; 
            // получаем body пердмета из метода
            $offerData = $this->buildOfferData($item);

            // отправляем запрос на создания offer
            $response = $this->client->post($url, [ 
                'headers' => $this->buildJsonHeaders($accessToken),
                'json' => $offerData,
            ]);

            $responseBody = $response->getBody()->getContents(); 
            $this->logger->info('Response from eBay: ' . $responseBody);

            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($responseBody, true);
                return $responseData;
            }

            $responseData = json_decode($responseBody, true); 
            return $responseData ?: null;

        } catch (\Exception $e) {
            $this->logger->error('Error creating offer for SKU: ' . $sku . ' - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Метод для получения предложения по SKU с eBay
     *
     * @param string $sku SKU товара.
     * @return array|null Ответ от API eBay со списком предложений или null в случае ошибки.
     */
    public function getOffers(string $sku): ?array
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

            // Формируем URL для запроса предложения по SKU
            $url =  $this->baseUrl . '/sell/inventory/v1/offer?sku=' . $sku;

            // Отправляем GET запрос для получения предложения по SKU
            $response = $this->client->get($url, [ 
                'headers' => $this->buildAuthHeaders($accessToken),
            ]);
    
            $responseBody = $response->getBody()->getContents();

            // Декодируем и возвращаем данные из ответа
            return json_decode($responseBody, true); 
        } catch (\Exception $e) {
            $this->logger->error('Error fetching offer by SKU - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Метод для получения предложения по offerId с eBay
     *
     * @param string $offerId ID предложения.
     * @return array|null Ответ от API eBay с информацией о предложении или null в случае ошибки.
     */
    public function getOfferByOfferId(string $offerId): ?array
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
    
            // Формируем URL для запроса предложения по offerId
            $url = $this->baseUrl . '/sell/inventory/v1/offer/' . $offerId;

            // Отправляем GET запрос для получения предложения по offerId
            $response = $this->client->get($url, [ 
                'headers' => $this->buildAuthHeaders($accessToken),
            ]);
    
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching offer by offerId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Метод для получения предложения по offerId с eBay
     *
     * @param string $offerId ID предложения для удаления.
     * @return bool True в случае успешного удаления, false в случае ошибки.
     */
    public function deleteOfferByOfferId(string $offerId): bool
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
    
            // Формируем URL для удаления предложения по offerId
            $url =  $this->baseUrl . '/sell/inventory/v1/offer/' . $offerId; 
    
            // Отправляем DELET запрос для удаления предложения по offerId
            $response = $this->client->delete($url, [
                'headers' => $this->buildAuthHeaders($accessToken),
            ]);
    
            if ($response->getStatusCode() === 204) {
                $this->logger->info("Offer with offerId $offerId deleted successfully.");
                return true;
            } else {
                $this->logger->error("Failed to delete offer with offerId $offerId. Status: " . $response->getStatusCode());
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error("Error deleting offer from eBay - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Метод для публикации оффера по offerId
     *
     * @param string $offerId ID предложения для публикации.
     * @return array|null Ответ от API eBay после публикации предложения или null в случае ошибки.
     */
    public function publishOffer(string $offerId): ?array 
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

            // Формируем URL публикации
            $url =  $this->baseUrl . '/sell/inventory/v1/offer/' . $offerId . '/publish'; 

            // POST-запрос на публикацию оффера
            $response = $this->client->post($url, [ 
                'headers' => $this->buildJsonHeaders($accessToken),
            ]);

            $responseBody = $response->getBody()->getContents();

            // Возвращаем ответ от eBay
            return json_decode($responseBody, true); 
        } catch (\Exception $e) {
            $this->logger->error('Error publishing offer - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Метод для получения AccesToken
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
     * Метод для для создания Offer body
     *
     * @param InventoryItem $item Сущность товара из базы данных.
     * @return array Массив с данными предложения в формате, ожидаемом API eBay.
     */
    private function buildOfferData(InventoryItem $item): array
    {
        return [
            'sku' => $item->getSku(),
            'marketplaceId' => $item->getMarketplaceId(),
            'format' => $item->getFormat(),
            'listingDescription' => '<p>' . $item->getDescription() . '</p>',
            'availableQuantity' => $item->getAvailabilityQuantity(),
            'pricingSummary' => [
                'price' => [
                    'value' => $item->getPrice(),
                    'currency' => $item->getCurrency(),
                ],
            ],
            'merchantLocationKey' => 'TEST1', //Полу которое нужно указать для работы публикации
            'categoryId' => '58058',
            "listingPolicies" => [
                "fulfillmentPolicyId" => "TEST1", //Полу которое нужно указать для работы публикации
                "paymentPolicyId"=> "TEST1", //Полу которое нужно указать для работы публикации
                "returnPolicyId" => "TEST1" //Полу которое нужно указать для работы публикации
            ],
        ];
    }

    /**
     * Получает InventoryItem по SKU.
     *
     * @param string $sku SKU товара для поиска.
     * @return InventoryItem|null Найденный товар или null, если товар не найден.
     */
    public function getInventoryItemBySku(string $sku): ?InventoryItem
    {
        return $this->inventoryItemRepository->findBySku($sku);
    }

}
