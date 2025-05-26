<?php

namespace App\Repository;

use App\Entity\EbayListing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<EbayListing>
 */
class EbayListingRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, EbayListing::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Находит листинг по SKU
     */
    public function findOneBySku(string $sku): ?EbayListing
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sku = :sku')
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Получает все листинги по статусу
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Возвращает активные листинги
     */
    public function findActiveListings(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->getQuery()
            ->getResult();
    }

    /**
     * Находит листинги по заданным критериям.
     *
     * @param array $criteria Массив с критериями фильтрации (например, ['id' => 123, 'sku' => 'test']).
     * @return array
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('e');

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
     * Сохраняет или обновляет листинг
     */
    public function save(EbayListing $listing, bool $flush = true): void
    {
        $this->entityManager->persist($listing);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Удаляет листинг
     */
    public function remove(EbayListing $listing, bool $flush = true): void
    {
        $this->entityManager->remove($listing);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}