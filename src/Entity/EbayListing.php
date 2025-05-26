<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\EbayListingRepository;

#[ORM\Entity(repositoryClass: EbayListingRepository::class)]
#[ORM\Table(name: "ebay_listings")]
class EbayListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private InventoryItem $inventoryItem;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $sku;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $offerId = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $marketplaceId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventoryItem(): InventoryItem
    {
        return $this->inventoryItem;
    }

    public function setInventoryItem(InventoryItem $inventoryItem): self
    {
        $this->inventoryItem = $inventoryItem;
        return $this;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    public function setOfferId(?string $offerId): self
    {
        $this->offerId = $offerId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getMarketplaceId(): ?string
    {
        return $this->marketplaceId;
    }

    public function setMarketplaceId(?string $marketplaceId): self
    {
        $this->marketplaceId = $marketplaceId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
