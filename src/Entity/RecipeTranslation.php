<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeTranslationRepository;
use App\Entity\Locale;
use App\Entity\GroupComponent;
use App\Entity\RecipeStep;
use App\Entity\Recipe;
use App\Entity\User;

#[ORM\Entity(repositoryClass: RecipeTranslationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RecipeTranslation
{
    use TimestampableTrait;
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
    private ?string $confirmation;

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
        targetEntity: GroupComponent::class,
        mappedBy: 'recipetranslation',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $groupcomponents;
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

    public function __construct()
    {
        $this->groupcomponents = new ArrayCollection();
        $this->recipesteps = new ArrayCollection();
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
    public function setConfirmation(?string $confirmation): self
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    public function getConfirmation(): ?string
    {
        return $this->confirmation;
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

    public function addGroupComponent(GroupComponent $groupcomponent): self
    {
        if(!$this->groupcomponents->contains($groupcomponent)) {
           $this->groupcomponents[] = $groupcomponent;
           $groupcomponent->setRecipetranslation($this);
        }
        return $this;
    }

    public function removeGroupComponent(GroupComponent $groupcomponent): self
    {
        if($this->groupcomponents->removeElement($groupcomponent)) {
           if ($groupcomponent->getRecipetranslation() === $this) {
               $groupcomponent->setRecipetranslation(null);
           }
        }
        return $this;
    }

    public function getGroupcomponents(): ?Collection
    {
        return $this->groupcomponents;
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