<?php

namespace App\Service;

use App\Entity\EbayListing;
use App\Entity\InventoryItem;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EbayListingRepository;

class EbayListingService
{
    private EbayListingRepository $repository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EbayListingRepository $repository,
        EntityManagerInterface $entityManager
    )
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
    }

    /**
     * Получает все листинги eBay и форматирует их для вывода.
     *
     * @return array Массив с отформатированными данными листингов.
     */
    public function getAllListingsFormatted(): array
    {
        $listings = $this->repository->findAll();
        return $this->formatListings($listings);
    }

    /**
     * Получает листинги eBay по заданным критериям и форматирует их.
     *
     * @param array $criteria Массив критериев для поиска листингов.
     * @return array Массив с отформатированными данными листингов, соответствующих критериям.
     */
    public function getListingsByCriteriaFormatted(array $criteria): array
    {
        $listings = $this->repository->findByCriteria($criteria);
        return $this->formatListings($listings);
    }

    /**
     * Форматирует массив объектов EbayListing в простой массив данных.
     *
     * @param array $listings Массив объектов EbayListing.
     * @return array Массив с отформатированными данными листингов.
     */
    private function formatListings(array $listings): array
    {
        $formatted = [];
        foreach ($listings as $listing) {
            $formatted[] = [
                'ID' => $listing->getId(),
                'SKU' => $listing->getSku(),
                'Offer ID' => $listing->getOfferId(),
                'Status' => $listing->getStatus(),
                'Marketplace ID' => $listing->getMarketplaceId(),
                'Start Time' => $listing->getStartTime()?->format('Y-m-d H:i:s'),
                'End Time' => $listing->getEndTime()?->format('Y-m-d H:i:s'),
                'Created At' => $listing->getCreatedAt()->format('Y-m-d H:i:s'),
                'Updated At' => $listing->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'Ithem Data' => $listing->getInventoryItem(),
            ];
        }
        return $formatted;
    }

    /**
     * Получает листинги по заданным критериям.
     *
     * @param array $criteria Массив с критериями фильтрации (например, ['id' => 123, 'sku' => 'test']).
     * @return array Массив объектов EbayListing, соответствующих критериям.
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->repository->createQueryBuilder('e');

        foreach ($criteria as $key => $value) {
            if (in_array($key, ['id', 'sku', 'offerId', 'status'])) {
                $qb->andWhere("e.{$key} = :{$key}")
                   ->setParameter($key, $value);
            }
            // Вы можете добавить больше условий для других полей, если это необходимо
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Сохраняет информацию о листинге eBay в базе данных.
     *
     * @param InventoryItem $inventoryItem Объект InventoryItem, связанный с листингом.
     * @param string $sku SKU товара.
     * @param array $ebayResponse Массив с данными от API eBay.
     * @return EbayListing|null Сохраненный или обновленный объект EbayListing.
     */
    public function saveEbayListing(InventoryItem $inventoryItem, string $sku, array $ebayResponse): ?EbayListing
    {
        $ebayListing = $this->repository->findOneBySku($sku);

        if (!$ebayListing) {
            $ebayListing = new EbayListing();
            $ebayListing->setInventoryItem($inventoryItem);
            $ebayListing->setSku($sku);
            $ebayListing->setCreatedAt(new \DateTimeImmutable());
        }

        // Обновляем данные из ответа eBay
        if (isset($ebayResponse['offerId'])) {
            $ebayListing->setOfferId($ebayResponse['offerId']);
        }
        if (isset($ebayResponse['status'])) {
            $ebayListing->setStatus($ebayResponse['status']);
        }
        if (isset($ebayResponse['startTime'])) {
            $ebayListing->setStartTime(new \DateTime($ebayResponse['startTime']));
        }
        if (isset($ebayResponse['endTime'])) {
            $ebayListing->setEndTime(new \DateTime($ebayResponse['endTime']));
        }
        if (isset($ebayResponse['marketplaceId'])) {
            $ebayListing->setMarketplaceId($ebayResponse['marketplaceId']);
        }

        $ebayListing->setUpdatedAt(new \DateTime());

        $this->repository->save($ebayListing, true);

        return $ebayListing;
    }

    /**
     * Удаляет запись EbayListing по offerId.
     *
     * @param string $offerId Offer ID листинга eBay.
     * @return bool True, если удаление прошло успешно, false если листинг не найден.
     */
    public function deleteByOfferId(string $offerId): bool
    {
        $ebayListing = $this->repository->findOneBy(['offerId' => $offerId]);

        if (!$ebayListing) {
            return false;
        }

        $this->entityManager->remove($ebayListing);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Обновляет информацию о листинге eBay после публикации.
     *
     * @param string $offerId Offer ID листинга eBay.
     * @param array|null $publishResult Данные, возвращенные API eBay при публикации (если есть).
     * @return EbayListing|null Обновленный объект EbayListing или null, если не найден.
     */
    public function updateAfterPublish(string $offerId, ?array $publishResult = null): ?EbayListing
    {
        $ebayListing = $this->repository->findOneBy(['offerId' => $offerId]);

        if ($ebayListing) {
            
            if (isset($publishResult['startTime'])) {
                $ebayListing->setStartTime(new \DateTime($publishResult['startTime']));
            }
            if (isset($publishResult['endTime'])) {
                $ebayListing->setEndTime(new \DateTime($publishResult['endTime']));
            }
            if (isset($publishResult['marketplaceId'])) {
                $ebayListing->setMarketplaceId($publishResult['marketplaceId']);
            }
            if (isset($publishResult['itemId'])) {
                $ebayListing->setEbayItemId($publishResult['itemId']);
            }

            $ebayListing->setStatus('Active');
            $ebayListing->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();
            return $ebayListing;
        }

        return null;
    }

    
}