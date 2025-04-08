<?php

namespace App\Repository;

use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

//Репозиторий для даблицы Inventory Item

/**
 * @extends ServiceEntityRepository<InventoryItem>
 *
 * @method InventoryItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method InventoryItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method InventoryItem[]    findAll()
 * @method InventoryItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    public function save(InventoryItem $entity, bool $flush = false): void //добавление в таблицу 
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllItems(): array //Вывод всех товаров 
    {
        return $this->findAll();
    }

    public function findBySku(string $sku): ?InventoryItem //Вывод определенного товара
    {
        return $this->findOneBy(['sku' => $sku]);
    }

    public function deleteBySku(string $sku, bool $flush = true): void //Удаление определенного товара
    {
        $entityManager = $this->getEntityManager();
        
        $item = $this->findOneBy(['sku' => $sku]);
        
        $entityManager->remove($item);
        
        if ($flush) {
            $entityManager->flush();
        }

    }
    

}
