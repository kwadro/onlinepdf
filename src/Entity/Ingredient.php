<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\IngredientRepository;
use App\Entity\Unit;
use App\Entity\Component;
use App\Entity\Product;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ingredient
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $sku;
    #[ORM\ManyToOne(
            targetEntity: Unit::class,
            cascade: ['persist'],
            inversedBy: 'ingredients'
    )]
        private ?Unit $unit;
    #[ORM\OneToMany(
        targetEntity: Component::class,
        mappedBy: 'ingredient',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $components;
    #[ORM\OneToMany(
        targetEntity: Product::class,
        mappedBy: 'ingredient',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $products;

    public function __construct()
    {
        $this->components = new ArrayCollection();
        $this->products = new ArrayCollection();
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
    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }
    public function setUnit(?Unit $unit): Ingredient
    {
        $this->unit = $unit;
        return $this;
    }

    public function addComponent(Component $component): self
    {
        if(!$this->components->contains($component)) {
           $this->components[] = $component;
           $component->setIngredient($this);
        }
        return $this;
    }

    public function removeComponent(Component $component): self
    {
        if($this->components->removeElement($component)) {
           if ($component->getIngredient() === $this) {
               $component->setIngredient(null);
           }
        }
        return $this;
    }

    public function getComponents(): ?Collection
    {
        return $this->components;
    }

    public function addProduct(Product $product): self
    {
        if(!$this->products->contains($product)) {
           $this->products[] = $product;
           $product->setIngredient($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if($this->products->removeElement($product)) {
           if ($product->getIngredient() === $this) {
               $product->setIngredient(null);
           }
        }
        return $this;
    }

    public function getProducts(): ?Collection
    {
        return $this->products;
    }

}
