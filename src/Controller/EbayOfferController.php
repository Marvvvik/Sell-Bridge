<?php

namespace App\Controller;

use App\Service\EbayOffersService;
use App\Service\EbayListingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class EbayOfferController extends AbstractController
{
    private $ebayOffersService;
    private $ebayListingService;
    private $logger;

    public function __construct(EbayOffersService $ebayOffersService, EbayListingService $ebayListingService, LoggerInterface $logger)
    {
        $this->ebayOffersService = $ebayOffersService;
        $this->ebayListingService = $ebayListingService;
        $this->logger = $logger;
    }

    /**
     * Маршрут для создания предложения товара на eBay по SKU
     */
    #[Route('/ebay/offer/add/{sku}', name: 'add_ebay_offer_api_item', methods: ['POST'])]
    public function addOffer(string $sku): Response
    {
        // Вызываем метод создания предложения из сервиса EbayOffersService
        $ebayResponse = $this->ebayOffersService->createOfferFromDatabaseItem($sku);

        if ($ebayResponse) {
            $this->logger->info('Offer successfully created on eBay with SKU: ' . $sku);
            // Получаем InventoryItem для сохранения в EbayListing
            $inventoryItem = $this->ebayOffersService->getInventoryItemBySku($sku);
            // Сохраняем информацию о листинге в нашу базу данных
            $ebayListing = $this->ebayListingService->saveEbayListing($inventoryItem, $sku, $ebayResponse);
            if ($ebayListing) {
                $this->logger->info('Successfully to save eBay listing for SKU: ' . $sku);
                return $this->json(['message' => 'Offer created on eBay and listing saved successfully.', 'ebay_response' => $ebayResponse, 'ebay_listing_id' => $ebayListing->getId()], 200);
            } else {
                $this->logger->error('Failed to save eBay listing for SKU: ' . $sku);
                return $this->json(['message' => 'Offer created on eBay, but failed to save listing details.'], 500);
            }
        }

        // Если создание предложения не удалось, логируем это и возвращаем ошибку
        $this->logger->error('Failed to create offer on eBay with SKU: ' . $sku);
        return $this->json(['message' => 'Failed to create offer on eBay.'], 400);
    }

    /**
     * Маршрут для получения предложения по SKU
     */
    #[Route('/ebay/offers/item/{sku}', name: 'get_ebay_offer_sku', methods: ['GET'])]
    public function getOffers(string $sku): JsonResponse
    {
        $result = $this->ebayOffersService->getOffers($sku); 
    
        if ($result) {
            $this->logger->info('Offer item retrieved successfully for SKU: ' . $sku);
            return $this->json(['message' => 'Offer item retrieved successfully.', 'data' => $result]); 
        }
    
        $this->logger->error('Failed to retrieve offer item for SKU: ' . $sku);
        return $this->json(['message' => 'Failed to retrieve offer item for SKU: ' . $sku], 400); 
    }

    /**
     * Маршрут для получения предложения по offerId с eBay
     */
    #[Route('/ebay/offer/item/{offerId}', name: 'get_ebay_offer_offerId', methods: ['GET'])]
    public function getOfferByOfferId(string $offerId): JsonResponse
    {
        $result = $this->ebayOffersService->getOfferByOfferId($offerId);
    
        if ($result) {
            $this->logger->info('Offer retrieved successfully.');
            return $this->json(['message' => 'Offer retrieved successfully.', 'data' => $result]); 
        }
    
        $this->logger->error('Failed to retrieve offer with offerId: ' . $offerId);
        return $this->json(['message' => 'Failed to retrieve offer with offerId: ' . $offerId], 404); 
    }

    /**
     * Маршрут для удаления предложения по offerId с eBay
     */
    #[Route('/ebay/offer/delete/{offerId}', name: 'delete_ebay_offer_offerid', methods: ['DELETE'])]
    public function deleteOffer(string $offerId): JsonResponse
    {
        $result = $this->ebayOffersService->deleteOfferByOfferId($offerId); 

        if ($result) {
            $this->logger->info('Offer with offerId ' . $offerId . ' successfully deleted from eBay.');
            // удаляем информацию о листинге по offer id
            $deleted = $this->ebayListingService->deleteByOfferId($offerId);
            if ($deleted) {
                $this->logger->info('Local EbayListing entity removed for offerId: ' . $offerId);
            } else {
                $this->logger->warning('EbayListing entity not found for offerId: ' . $offerId);
            }

            return $this->json(['message' => "Offer with offerId $offerId successfully deleted from eBay and listing removed."], 200);
        }
        $this->logger->error('Failed to delete offer with offerId ' . $offerId . ' from eBay.');
        return $this->json(['message' => "Failed to delete offer with offerId $offerId from eBay."], 400);
    }

    /**
     * Маршрут для публикации оффера
     */
    #[Route('/ebay/offer/publish/{offerId}', name: 'publish_ebay_offer', methods: ['POST'])]
    public function publishOffer(string $offerId): JsonResponse
    {
        $publishResult = $this->ebayOffersService->publishOffer($offerId);

        if ($publishResult) {
            $this->logger->info('Offer with ID ' . $offerId . ' successfully published on eBay.');
            // Обновляем информацию о листинге в нашей базе данных
            $ebayListing = $this->ebayListingService->updateAfterPublish($offerId, $publishResult);

            if ($ebayListing) {
                $this->logger->info('Successfully updated eBay listing after publish for offerId: ' . $offerId);
                return $this->json(['message' => "Offer with ID $offerId successfully published on eBay and listing updated.", 'data' => $publishResult, 'ebay_listing_id' => $ebayListing->getId()], 200);
            } else {
                $this->logger->warning('Could not find eBay listing to update after publish for offerId: ' . $offerId);
                return $this->json(['message' => "Offer with ID $offerId successfully published on eBay, but could not update listing details.", 'data' => $publishResult], 200);
            }
        }

        $this->logger->error('Failed to publish offer with ID: ' . $offerId);
        return $this->json(['message' => "Failed to publish offer with ID: $offerId"], 400);
    }
}