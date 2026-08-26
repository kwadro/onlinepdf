<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\ComponentRepository;
use App\Entity\Ingredient;
use App\Entity\Unit;
use App\Entity\GroupComponent;

#[ORM\Entity(repositoryClass: ComponentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Component
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;
    #[ORM\ManyToOne(
            targetEntity: Ingredient::class,
            cascade: ['persist'],
            inversedBy: 'components'
    )]
        private ?Ingredient $ingredient;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $quantity;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $textunit;
    #[ORM\ManyToOne(
            targetEntity: Unit::class,
            cascade: ['persist'],
            inversedBy: 'components'
    )]
        private ?Unit $unit;
    #[ORM\ManyToOne(
            targetEntity: GroupComponent::class,
            cascade: ['persist'],
            inversedBy: 'components'
    )]
        private ?GroupComponent $groupcomponent;

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
    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
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
    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }
    public function setTextunit(?string $textunit): self
    {
        $this->textunit = $textunit;
        return $this;
    }

    public function getTextunit(): ?string
    {
        return $this->textunit;
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

    public function getGroupcomponent(): ?GroupComponent
    {
        return $this->groupcomponent;
    }
    public function setGroupcomponent(?GroupComponent $groupcomponent): Component
    {
        $this->groupcomponent = $groupcomponent;
        return $this;
    }

    public function __toString(): string
    {
      return
          $this->ingredient->getName() . '|' . 
          $this->quantity . '|' . 
          $this->unit->getName();

    }
        public function getName(): string
        {
            return
                      $this->ingredient->getName() . '|' . 
                      $this->quantity . '|' . 
                      $this->unit->getName();
        }

}
