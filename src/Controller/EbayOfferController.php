<?php

namespace App\Controller;

use App\Service\EbayOffersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class EbayOfferController extends AbstractController
{
    private $ebayOffersService;
    private $logger;

    public function __construct(EbayOffersService $ebayOffersService, LoggerInterface $logger)
    {
        $this->ebayOffersService = $ebayOffersService;
        $this->logger = $logger;
    }

    #[Route('/ebay/offer/add/{sku}', name: 'add_ebay_offer_api_item', methods: ['POST'])] // Маршрут для создания предложения товара на eBay по SKU
    public function addOffer(string $sku): Response
    {
        $result = $this->ebayOffersService->createOfferFromDatabaseItem($sku); // Вызываем метод создания предложения из сервиса

        if ($result) { // Если предложение успешно создано, логируем это и возвращаем ответ с успешным статусом
            $this->logger->info('Offer successfully created on eBay with SKU: ' . $sku);
            return $this->json(['message' => 'Offer created on eBay successfully.', 'data' => $result], 200);
        }

        // Если создание предложения не удалось, логируем это и возвращаем ошибку
        $this->logger->error('Failed to create offer on eBay with SKU: ' . $sku);
        return $this->json(['message' => 'Failed to create offer on eBay.'], 400);
    }

    #[Route('/ebay/offers/item/{sku}', name: 'get_ebay_offer_sku', methods: ['GET'])] // Маршрут для получения предложения по SKU
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

    #[Route('/ebay/offer/item/{offerId}', name: 'get_ebay_offer_offerId', methods: ['GET'])] // Маршрут для получения предложения по offerId с eBay
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

    #[Route('/ebay/offer/delete/{offerId}', name: 'delete_ebay_offer_offerid', methods: ['DELETE'])] // Маршрут для удаления предложения по offerId с eBay
    public function deleteOffer(string $offerId): JsonResponse
    {
        $result = $this->ebayOffersService->deleteOfferByOfferId($offerId); 
    
        if ($result) {
            $this->logger->info('Offer with offerId ' . $offerId . ' successfully deleted from eBay.');
            return $this->json(['message' => "Offer with offerId $offerId successfully deleted from eBay."], 200);
        }
    
        $this->logger->error('Failed to delete offer with offerId ' . $offerId . ' from eBay.');
        return $this->json(['message' => "Failed to delete offer with offerId $offerId from eBay."], 400);
    }

    #[Route('/ebay/offer/publish/{offerId}', name: 'publish_ebay_offer', methods: ['POST'])] // Маршрут для публикации оффера
    public function publishOffer(string $offerId): JsonResponse
    {
        $result = $this->ebayOffersService->publishOffer($offerId); 

        if ($result) {
            $this->logger->info('Offer with ID ' . $offerId . ' successfully published on eBay.');
            return $this->json(['message' => "Offer with ID $offerId successfully published on eBay.",'data' => $result], 200);
        }

        $this->logger->error('Failed to publish offer with ID: ' . $offerId);
        return $this->json(['message' => "Failed to publish offer with ID: $offerId"], 400);
    }
}