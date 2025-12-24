<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\ComponentRepository;
use App\Entity\Ingredient;
use App\Entity\Unit;
use App\Entity\Recipe;

#[ORM\Entity(repositoryClass: ComponentRepository::class)]
class Component
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;
    #[ORM\ManyToOne(
            targetEntity: Ingredient::class,
            cascade: ['persist'],
            inversedBy: 'components'
    )]
        private ?Ingredient $ingredient;
    #[ORM\ManyToOne(
            targetEntity: Unit::class,
            cascade: ['persist'],
            inversedBy: ''
    )]
        private ?Unit $unit;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $quantity;
    #[ORM\ManyToMany(
          targetEntity: Recipe::class,
            inversedBy: 'components',
            cascade: ['persist'],
            orphanRemoval: false,
    )]
        public ?Collection $recipes;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->recipes = new ArrayCollection();
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function __toString(): string
    {
        return $this->name;
    }

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }
    public function setIngredient(?Ingredient $ingredient): Component
    {
        $this->ingredient = $ingredient;
        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }
    public function setUnit(?Unit $unit): Component
    {
        $this->unit = $unit;
        return $this;
    }
    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }
    public function addRecipe(Recipe $recipe): self
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes->add($recipe);
            $recipe->addComponent($this);
        }
        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->removeElement($recipe)) {
            $recipe->removeComponent($this);
        }
        return $this;
    }

    public function getRecipes(): ?Collection
    {
        return $this->recipes;
    }

}