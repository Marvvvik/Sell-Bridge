<?php

namespace App\Service;

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

    public function __construct(Client $client, TokenService $tokenService, LoggerInterface $logger, InventoryItemRepository $inventoryItemRepository, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->Environment = $params->get('ebay_environment');
    }

    private function getApiUrl(string $endpoint): string // Метод для формирования URL в зависимости от среды
    {
        $baseUrl = match ($this->Environment) {
            'Production' => 'https://api.ebay.com',
            'Sandbox' => 'https://api.sandbox.ebay.com',
            default => throw new \InvalidArgumentException('Invalid environment specified.'),
        };

        return $baseUrl . $endpoint;
    }
    
    public function createOfferFromDatabaseItem(string $sku): ?array // Метод для создания предложения товара на eBay на основе данных из базы по SKU
    {
        try {
            $item = $this->inventoryItemRepository->findBySku($sku); // Получаем товар из базы данных по SKU

            if (!$item) { // Если товар не найден, логируем предупреждение и возвращаем null
                $this->logger->warning('Item not found in database with SKU: ' . $sku);
                return null;
            }

            $listingDescription = $item->getDescription();
            $listingDescriptionHtml = "<p>" . $listingDescription . "</p>";
            
            $offerData = [
                'sku' => $item->getSku(),
                'marketplaceId' => $item->getMarketplaceId(),
                'format' => $item->getFormat(), 
                'listingDescription' => $listingDescriptionHtml,
                'availableQuantity' => $item->getAvailabilityQuantity(),
                'pricingSummary' => [
                    'price' => [
                        'value' => $item->getPrice(),
                        'currency' => $item->getCurrency(),
                    ],
                ],
                'merchantLocationKey' => 'TEST1', // надо указать локацию 
                'categoryId' => '58058',
                'listingPolicies' => [
                    'fulfillmentPolicyId' => 'TEST1', // так же надо указать политики
                    'paymentPolicyId' => 'TEST1',
                    'returnPolicyId' => 'TEST1',
                ],
            ];

            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved for SKU: ' . $item->getSku());
            } else {
                $this->logger->warning('No access token retrieved for SKU: ' . $item->getSku());
            }

            $url = $this->getApiUrl('/sell/inventory/v1/offer'); // Формируем URL для создания предложения на eBay

            $response = $this->client->post($url, [ // Отправляем POST запрос для создания предложения товара
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Content-Language' => 'en-US',
                ],
                'json' => $offerData,
            ]);

            $responseBody = $response->getBody()->getContents(); // Получаем содержимое ответа от eBay
            $this->logger->info('Response from eBay: ' . $responseBody);

            if ($response->getStatusCode() === 200) { // Проверяем код ответа
                return json_decode($responseBody, true); // Возвращаем данные предложения
            }

            $responseData = json_decode($responseBody, true); // Если ответ содержит ошибки, возвращаем их
            return $responseData ? $responseData : null;

        } catch (\Exception $e) {
            $this->logger->error('Error creating offer for SKU: ' . $sku . ' - ' . $e->getMessage());
            return null;
        }
    }

    public function getOffers(string $sku): ?array // Метод для получения предложения по SKU с eBay
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved');
            } else {
                $this->logger->warning('No access token retrieved');
            }
            
            $url = $this->getApiUrl('/sell/inventory/v1/offer?sku=' . $sku);// Формируем URL для запроса предложения по SKU
    
            $response = $this->client->get($url, [ // Отправляем GET запрос для получения предложения по SKU
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
    
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true); // Декодируем и возвращаем данные из ответа
        } catch (\Exception $e) {
            $this->logger->error('Error fetching offer by SKU - ' . $e->getMessage());
            return null;
        }
    }

    public function getOfferByOfferId(string $offerId): ?array // Метод для получения предложения по offerId с eBay
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved');
            } else {
                $this->logger->warning('No access token retrieved');
            }
    
            $url = $this->getApiUrl('/sell/inventory/v1/offer/' . $offerId);// Формируем URL для запроса предложения по offerId

            $response = $this->client->get($url, [ // Отправляем GET запрос для получения предложения по offerId
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
    
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching offer by offerId - ' . $e->getMessage());
            return null;
        }
    }

    public function deleteOfferByOfferId(string $offerId): bool // Метод для удаления предложения по offerId с eBay
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay
            if (!$accessToken) {
                $this->logger->warning("Access token not found for offerId: $offerId");
                return false;
            }
    
            $url = $this->getApiUrl('/sell/inventory/v1/offer/' . $offerId); // Формируем URL для удаления предложения по offerId
    
            $response = $this->client->delete($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
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

    public function publishOffer(string $offerId): ?array // Метод для публикации оффера по offerId
    {
        try {
            $accessToken = $this->tokenService->getValidAccessToken(); // Получаем токен доступа для eBay

            if ($accessToken) {
                $this->logger->info('Access token successfully retrieved for publishing offer');
            } else {
                $this->logger->warning('No access token retrieved for publishing offer');
                return null;
            }

            $url = $this->getApiUrl('/sell/inventory/v1/offer/' . $offerId . '/publish'); // Формируем URL публикации

            $response = $this->client->post($url, [ // POST-запрос на публикацию оффера
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true); // Возвращаем ответ от eBay
        } catch (\Exception $e) {
            $this->logger->error('Error publishing offer - ' . $e->getMessage());
            return null;
        }
    }

}
