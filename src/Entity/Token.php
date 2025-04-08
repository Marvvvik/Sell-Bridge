<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

//схема таблицы для Access token и Refresh Token 
#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\Table(name: "ebay_tokens")]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $access_token = null;

    #[ORM\Column(type: 'text')]
    private ?string $refresh_token = null;

    #[ORM\Column]
    private ?int $expires_in = null;

    #[ORM\Column]
    private ?int $refresh_token_expires_in = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int { return $this->id; }
    public function setAccessToken(?string $access_token): self { $this->access_token = $access_token; return $this; }
    public function getAccessToken(): ?string { return $this->access_token; }
    public function setRefreshToken(?string $refresh_token): self { $this->refresh_token = $refresh_token; return $this; }
    public function getRefreshToken(): ?string { return $this->refresh_token; }
    public function setExpiresIn(?int $expires_in): self { $this->expires_in = $expires_in; return $this; }
    public function getExpiresIn(): ?int { return $this->expires_in; }
    public function setRefreshTokenExpiresIn(?int $refresh_token_expires_in): self { $this->refresh_token_expires_in = $refresh_token_expires_in; return $this; }
    public function getRefreshTokenExpiresIn(): ?int { return $this->refresh_token_expires_in; }
    public function setCreatedAt(?\DateTimeImmutable $created_at): self { $this->created_at = $created_at; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->created_at; }
    public function setUpdatedAt(?\DateTimeImmutable $updated_at): self { $this->updated_at = $updated_at; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updated_at; }
}