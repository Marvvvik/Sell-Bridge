<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

//схема таблицы для Inventory Item
#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\Table(name: "ebay_inventory_items")]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true)]
    private ?string $sku = null;

    #[ORM\Column(type: 'string')]
    private ?string $locale = null;

    #[ORM\Column(type: 'string')]
    private ?string $title = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $size = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $material = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $imageUrls = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $itemCondition  = null;

    #[ORM\Column(type: 'float')]
    private ?float $price = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantity = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $mpn  = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $availability_quantity = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $marketplaceId = null; 

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $format = null;

    public function getId(): ?int { return $this->id; }

    public function getSku(): ?string { return $this->sku; }
    public function setSku(string $sku): self { $this->sku = $sku; return $this; }

    public function getLocale(): ?string { return $this->locale; }
    public function setLocale(string $locale): self { $this->locale = $locale; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getSize(): ?string { return $this->size; }
    public function setSize(?string $size): self { $this->size = $size; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): self { $this->color = $color; return $this; }

    public function getMaterial(): ?string { return $this->material; }
    public function setMaterial(?string $material): self { $this->material = $material; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getBrand(): ?string { return $this->brand; }
    public function setBrand(?string $brand): self { $this->brand = $brand; return $this; }

    public function getImageUrls(): ?array { return $this->imageUrls; }
    public function setImageUrls(?array $imageUrls): self { $this->imageUrls = $imageUrls; return $this; }

    public function getItemCondition(): ?string { return $this->itemCondition; }
    public function setItemCondition(?string $itemCondition): self { $this->itemCondition = $itemCondition; return $this; }

    public function getPrice(): ?float { return $this->price; }
    public function setPrice(float $price): self { $this->price = $price; return $this; }

    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(?string $currency): self { $this->currency = $currency; return $this; }

    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }

    public function getAvailabilityQuantity(): ?int { return $this->availability_quantity; }
    public function setAvailabilityQuantity(?int $availability_quantity): self { $this->availability_quantity = $availability_quantity; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->created_at; }
    public function setCreatedAt(?\DateTimeImmutable $created_at): self { $this->created_at = $created_at; return $this; }

    public function getMarketplaceId(): ?string { return $this->marketplaceId; }
    public function setMarketplaceId(?string $marketplaceId): self { $this->marketplaceId = $marketplaceId; return $this; }

    public function getFormat(): ?string { return $this->format; }
    public function setFormat(?string $format): self { $this->format = $format; return $this; }
    
    public function getMPN(): ?string { return $this->mpn; }
    public function setMPN(?string $mpn): self { $this->mpn = $mpn; return $this; }
}

