<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\GroupComponentRepository;
use App\Entity\RecipeTranslation;
use App\Entity\Component;

#[ORM\Entity(repositoryClass: GroupComponentRepository::class)]
class GroupComponent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;
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

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->components = new ArrayCollection();
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