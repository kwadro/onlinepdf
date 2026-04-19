<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeTranslationRepository;
use App\Entity\Locale;
use App\Entity\Component;
use App\Entity\RecipeStep;
use App\Entity\Recipe;
use App\Entity\User;

#[ORM\Entity(repositoryClass: RecipeTranslationRepository::class)]
class RecipeTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Locale::class,
            cascade: ['persist'],
            inversedBy: 'recipelocales'
    )]
        private ?Locale $locale;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $slug;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $is_active;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $publish;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $is_popular;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $meta_title;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $meta_description;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $short_description;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $description;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $cuisine;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $notes;
    #[ORM\OneToMany(
        targetEntity: Component::class,
        mappedBy: 'recipetranslation',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $components;
    #[ORM\OneToMany(
        targetEntity: RecipeStep::class,
        mappedBy: 'recipetranslation',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
        public ?Collection $recipesteps;
    #[ORM\ManyToOne(
            targetEntity: Recipe::class,
            cascade: ['persist'],
            inversedBy: 'recipetranslations'
    )]
        private ?Recipe $recipe;
    #[ORM\ManyToOne(
            targetEntity: User::class,
            cascade: ['persist'],
            inversedBy: 'recipes'
    )]
        private ?User $user;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->components = new ArrayCollection();
        $this->recipesteps = new ArrayCollection();
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

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }
    public function setLocale(?Locale $locale): RecipeTranslation
    {
        $this->locale = $locale;
        return $this;
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
    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
    public function setIsActive(?string $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getIsActive(): ?string
    {
        return $this->is_active;
    }
    public function setPublish(?string $publish): self
    {
        $this->publish = $publish;
        return $this;
    }

    public function getPublish(): ?string
    {
        return $this->publish;
    }
    public function setIsPopular(?string $is_popular): self
    {
        $this->is_popular = $is_popular;
        return $this;
    }

    public function getIsPopular(): ?string
    {
        return $this->is_popular;
    }
    public function setMetaTitle(?string $meta_title): self
    {
        $this->meta_title = $meta_title;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->meta_title;
    }
    public function setMetaDescription(?string $meta_description): self
    {
        $this->meta_description = $meta_description;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->meta_description;
    }
    public function setShortDescription(?string $short_description): self
    {
        $this->short_description = $short_description;
        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->short_description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setCuisine(?string $cuisine): self
    {
        $this->cuisine = $cuisine;
        return $this;
    }

    public function getCuisine(): ?string
    {
        return $this->cuisine;
    }
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function addComponent(Component $component): self
    {
        if(!$this->components->contains($component)) {
           $this->components[] = $component;
           $component->setRecipetranslation($this);
        }
        return $this;
    }

    public function removeComponent(Component $component): self
    {
        if($this->components->removeElement($component)) {
           if ($component->getRecipetranslation() === $this) {
               $component->setRecipetranslation(null);
           }
        }
        return $this;
    }

    public function getComponents(): ?Collection
    {
        return $this->components;
    }

    public function addRecipeStep(RecipeStep $recipestep): self
    {
        if(!$this->recipesteps->contains($recipestep)) {
           $this->recipesteps[] = $recipestep;
           $recipestep->setRecipetranslation($this);
        }
        return $this;
    }

    public function removeRecipeStep(RecipeStep $recipestep): self
    {
        if($this->recipesteps->removeElement($recipestep)) {
           if ($recipestep->getRecipetranslation() === $this) {
               $recipestep->setRecipetranslation(null);
           }
        }
        return $this;
    }

    public function getRecipesteps(): ?Collection
    {
        return $this->recipesteps;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }
    public function setRecipe(?Recipe $recipe): RecipeTranslation
    {
        $this->recipe = $recipe;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): RecipeTranslation
    {
        $this->user = $user;
        return $this;
    }

}