<?php

namespace App\Entity;

use App\Entity\Traits\TimeStampAbleTrait;
use App\Repository\RecipeViewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeViewRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RecipeView
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $id = null;
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $user_id = null;
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $recipe_id = null;

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
