<?php

namespace App\Entity;
use App\Repository\RecipeViewRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: RecipeViewRepository::class)]
class RecipeView
{
    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id = null;
    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'recently_viewed_recipes'
    )]
    private ?User $user;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updated_at = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }


    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
    public function __toString(): string
    {
        return $this->id;
    }
}
