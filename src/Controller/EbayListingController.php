<?php

namespace App\Controller;

use App\Service\EbayListingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class EbayListingController extends AbstractController
{
    private EbayListingService $listingService;

    public function __construct(EbayListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    /**
     * Маршрут для вывода листинга и фильтрации 
     */
    #[Route('/ebay/listings', name: 'ebay_listings_filter', methods: ['GET'])]
    public function filterListings(Request $request): JsonResponse
    {
        $criteria = $request->query->all();
        $listings = $this->listingService->getListingsByCriteriaFormatted($criteria);
        return $this->json([
            'status' => 'success',
            'listings' => $listings,
            'criteria' => $criteria,
        ]);
    }
}
