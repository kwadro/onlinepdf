<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\IngredientRepository;
use App\Entity\Component;

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

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $url;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $price;
    #[ORM\OneToMany(
        targetEntity: Component::class,
        mappedBy: 'ingredient',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $components;

    public function __construct()
    {
        $this->components = new ArrayCollection();
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

}
