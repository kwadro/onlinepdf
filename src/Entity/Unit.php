<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UnitRepository;
use App\Entity\Ingredient;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Unit
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $short_name;
    #[ORM\OneToMany(
        targetEntity: Ingredient::class,
        mappedBy: 'unit',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $ingredients;

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
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
    public function setShortName(?string $short_name): self
    {
        $this->short_name = $short_name;
        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->short_name;
    }
    public function __toString(): string
    {
        return $this->short_name;
    }

    public function addIngredient(Ingredient $ingredient): self
    {
        if(!$this->ingredients->contains($ingredient)) {
           $this->ingredients[] = $ingredient;
           $ingredient->setUnit($this);
        }
        return $this;
    }

    public function removeIngredient(Ingredient $ingredient): self
    {
        if($this->ingredients->removeElement($ingredient)) {
           if ($ingredient->getUnit() === $this) {
               $ingredient->setUnit(null);
           }
        }
        return $this;
    }

    public function getIngredients(): ?Collection
    {
        return $this->ingredients;
    }

}
