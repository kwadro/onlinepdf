<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\GroupComponentRepository;
use App\Entity\RecipeTranslation;
use App\Entity\Component;

#[ORM\Entity(repositoryClass: GroupComponentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class GroupComponent
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;
    #[ORM\ManyToOne(
            targetEntity: RecipeTranslation::class,
            cascade: ['persist'],
            inversedBy: 'groupcomponents'
    )]
        private ?RecipeTranslation $recipetranslation;
    #[ORM\OneToMany(
        targetEntity: Component::class,
        mappedBy: 'groupcomponent',
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
    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getRecipetranslation(): ?RecipeTranslation
    {
        return $this->recipetranslation;
    }
    public function setRecipetranslation(?RecipeTranslation $recipetranslation): GroupComponent
    {
        $this->recipetranslation = $recipetranslation;
        return $this;
    }

    public function addComponent(Component $component): self
    {
        if(!$this->components->contains($component)) {
           $this->components[] = $component;
           $component->setGroupcomponent($this);
        }
        return $this;
    }

    public function removeComponent(Component $component): self
    {
        if($this->components->removeElement($component)) {
           if ($component->getGroupcomponent() === $this) {
               $component->setGroupcomponent(null);
           }
        }
        return $this;
    }

    public function getComponents(): ?Collection
    {
        return $this->components;
    }

}