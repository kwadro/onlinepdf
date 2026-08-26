<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\ProductRepository;
use App\Entity\Unit;
use App\Entity\Ingredient;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $url;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $price;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $quantity;
    #[ORM\ManyToOne(
            targetEntity: Unit::class,
            cascade: ['persist'],
            inversedBy: 'components'
    )]
        private ?Unit $unit;
    #[ORM\ManyToOne(
            targetEntity: Ingredient::class,
            cascade: ['persist'],
            inversedBy: 'products'
    )]
        private ?Ingredient $ingredient;

    public function __construct()
    {
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
    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
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

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }
    public function setUnit(?Unit $unit): Product
    {
        $this->unit = $unit;
        return $this;
    }

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }
    public function setIngredient(?Ingredient $ingredient): Product
    {
        $this->ingredient = $ingredient;
        return $this;
    }

}
