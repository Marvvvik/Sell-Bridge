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

    public function __construct(
        InventoryItemRepository $inventoryItemRepository,
        LoggerInterface $logger
    )
    {
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->logger = $logger;  
    }

    /**
     * метод для получения данных
     * 
     * @param Request $request Объект HTTP-запроса с данными товара в формате JSON.
     * @return string|null Null в случае успешного добавления, сообщение об ошибке в противном случае.
     */
    public function addItemFromRequest(Request $request): ?string 
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

    /**
     * метод для записи данных в базу данных
     *
     * @param string $sku Артикул товара.
     * @param string $locale Локаль товара.
     * @param string $title Название товара.
     * @param string $size Размер товара.
     * @param string $color Цвет товара.
     * @param string $material Материал товара.
     * @param string|null $description Описание товара (может быть null).
     * @param string|null $brand Бренд товара (может быть null).
     * @param array $imageUrls Массив URL-адресов изображений товара.
     * @param string $condition Состояние товара.
     * @param float $price Цена товара.
     * @param string $currency Валюта цены.
     * @param int $quantity Доступное количество товара.
     * @param int $availability_quantity Количество для отображения доступности.
     * @param \DateTimeImmutable $createdAt Дата создания записи.
     * @param string $marketplaceId Идентификатор маркетплейса.
     * @param string $format Формат товара.
     * @param string $mpn MPN товара.
     * @return string|null Null в случае успеха, сообщение об ошибке в противном случае.
     */
    public function addItem(
    string $sku, string $locale, string $title, string $size, string $color, string $material,
    ?string $description, ?string $brand, array $imageUrls, string  $condition, float $price,
    string $currency, int $quantity, int $availability_quantity, \DateTimeImmutable $createdAt,
    string $marketplaceId, string $format, string $mpn): ?string
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

    /**
     * Метод для получения всех товаров из инвентаря
     *
     * @return array Массив всех товаров InventoryItem.
     */
    public function getItems(): array  
    {
        // Запрашиваем все товары из базы данных через репозиторий
        return $this->inventoryItemRepository->getAllItems();
    }

    /**
     * Метод для получения товара по SKU
     *
     * @param string $sku SKU товара для поиска.
     * @return InventoryItem|null Найденный товар или null, если товар не найден.
     */
    public function getItem(string $sku): ?InventoryItem
    {
        // Находим товар в базе данных по SKU
        return $this->inventoryItemRepository->findBySku($sku);
    }

    /**
     * Метод для удаления товара из инвентаря по SKU
     *
     * @param string $sku SKU товара для удаления.
     * @return void
     */
    public function deleteItem(string $sku): void
    {
        // Удаляем товар из базы данных по SKU через репозиторий
        $this->inventoryItemRepository->deleteBySku($sku, true);
    }
}
