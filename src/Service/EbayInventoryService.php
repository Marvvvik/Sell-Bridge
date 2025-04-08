<?php

namespace App\Service;

use App\Entity\InventoryItem;
use App\Repository\InventoryItemRepository;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class EbayInventoryService
{
    private $inventoryItemRepository;
    private $logger;

    public function __construct(InventoryItemRepository $inventoryItemRepository, LoggerInterface $logger)
    {
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->logger = $logger;  
    }

    public function addItemFromRequest(Request $request): ?string // метод для получения данных
    {
        $data = json_decode($request->getContent(), true);
    
        // Проверка наличия всех обязательных полей
        $requiredFields = [
            'sku', 'locale', 'product.title', 'product.aspects.Size', 'product.aspects.Color', 
            'product.aspects.Material', 'product.description', 'product.brand', 'product.imageUrls', 
            'condition', 'price.value', 'price.currency', 'quantity', 'availability_quantity', 
            'marketplaceId', 'format', 'product.mpn'
        ];

        foreach ($requiredFields as $field) {
            $keys = explode('.', $field);
            $value = $data;
            foreach ($keys as $key) {
                if (!isset($value[$key])) {
                    $this->logger->error("Missing field: $field");
                    return "Missing field: $field";
                }
                $value = $value[$key];
            }
        }
        // Теперь добавляем товар, если все поля присутствуют
        return $this->addItem(
            $data['sku'], 
            $data['locale'], 
            $data['product']['title'],
            $data['product']['aspects']['Size'][0], 
            $data['product']['aspects']['Color'][0], 
            $data['product']['aspects']['Material'][0], 
            $data['product']['description'], 
            $data['product']['brand'], 
            $data['product']['imageUrls'], 
            $data['condition'], 
            (float)$data['price']['value'], 
            $data['price']['currency'], 
            (int)$data['quantity'], 
            (int)$data['availability_quantity'], 
            new \DateTimeImmutable(), 
            $data['marketplaceId'], 
            $data['format'], 
            $data['product']['mpn']
        );
    }

    // метод для записи данных в базу данных
    public function addItem(string $sku, string $locale, string $title, string $size, string $color, string $material, ?string $description, ?string $brand, array $imageUrls, string 
    $condition, float $price, string $currency, int $quantity, int $availability_quantity, \DateTimeImmutable $createdAt, string $marketplaceId, string $format, string $mpn): ?string
    {
        try {
            $item = new InventoryItem();
            $item->setSku($sku);
            $item->setLocale($locale);
            $item->setTitle($title);
            $item->setSize($size);
            $item->setColor($color);
            $item->setMaterial($material);
            $item->setDescription($description);
            $item->setBrand($brand);
            $item->setImageUrls($imageUrls);
            $item->setItemCondition($condition);
            $item->setPrice($price);
            $item->setCurrency($currency);
            $item->setQuantity($quantity);
            $item->setAvailabilityQuantity($availability_quantity);
            $item->setCreatedAt($createdAt);
            $item->setMarketplaceId($marketplaceId);
            $item->setFormat($format);
            $item->setMpn($mpn);

            $this->inventoryItemRepository->save($item, true);

            return null;  
        } catch (\Exception $e) {
            $this->logger->error('Error adding item to inventory: ' . $e->getMessage());
            return 'Error adding item to inventory';
        }
    }

    public function getItems(): array  // Метод для получения всех товаров из инвентаря
    {
        return $this->inventoryItemRepository->getAllItems(); // Запрашиваем все товары из базы данных через репозиторий
    }

    public function getItem(string $sku): ?InventoryItem // Метод для получения товара по SKU
    {
        return $this->inventoryItemRepository->findBySku($sku); // Находим товар в базе данных по SKU
    }

    public function deleteItem(string $sku): void  // Метод для удаления товара из инвентаря по SKU
    {
        $this->inventoryItemRepository->deleteBySku($sku, true); // Удаляем товар из базы данных по SKU через репозиторий
    }
}
