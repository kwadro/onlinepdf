<?php

namespace App\Entity;
use App\Repository\RecipeViewRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeViewRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RecipeView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id = null;
    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $user_id = null;
    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $recipe_id = null ;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updated_at = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getRecipeId(): ?int
    {
        return $this->recipe_id;
    }
    public function setRecipeId($recipe_id): static
    {
        $this->recipe_id = $recipe_id;
        return $this;
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
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }
    public function __toString(): string
    {
        return $this->id;
    }
}
