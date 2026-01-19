<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeRepository;
use App\Entity\RecipeCategory;
use App\Entity\Component;
use App\Entity\RecipeStep;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $slug;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $is_active;

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

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $prep_time_min;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $cook_time_min;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $servings;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $notes;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $image;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $image1;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $image2;
    #[ORM\ManyToMany(
          targetEntity: RecipeCategory::class,
            mappedBy: 'recipes',
            cascade: ['persist'],
            orphanRemoval: false,
    )]
        public ?Collection $recipecategorys;
    #[ORM\ManyToMany(
          targetEntity: Component::class,
            mappedBy: 'recipes',
            cascade: ['persist'],
            orphanRemoval: false,
    )]
        public ?Collection $components;
    #[ORM\OneToMany(
        targetEntity: RecipeStep::class,
        mappedBy: 'recipe',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public ?Collection $recipesteps;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->recipecategorys = new ArrayCollection();
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
    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
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
    public function setPrepTimeMin(?int $prep_time_min): self
    {
        $this->prep_time_min = $prep_time_min;
        return $this;
    }

    public function getPrepTimeMin(): ?int
    {
        return $this->prep_time_min;
    }
    public function setCookTimeMin(?int $cook_time_min): self
    {
        $this->cook_time_min = $cook_time_min;
        return $this;
    }

    public function getCookTimeMin(): ?int
    {
        return $this->cook_time_min;
    }
    public function setServings(?int $servings): self
    {
        $this->servings = $servings;
        return $this;
    }

    public function getServings(): ?int
    {
        return $this->servings;
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
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
    public function setImage1(?string $image1): self
    {
        $this->image1 = $image1;
        return $this;
    }

    public function getImage1(): ?string
    {
        return $this->image1;
    }
    public function setImage2(?string $image2): self
    {
        $this->image2 = $image2;
        return $this;
    }

    public function getImage2(): ?string
    {
        return $this->image2;
    }
    public function addRecipecategory(RecipeCategory $recipecategory): self
    {
        if (!$this->recipecategorys->contains($recipecategory)) {
            $this->recipecategorys->add($recipecategory);
            $recipecategory->addRecipe($this);
        }
        return $this;
    }

    public function removeRecipecategory(RecipeCategory $recipecategory): self
    {
        if ($this->recipecategorys->removeElement($recipecategory)) {
            $recipecategory->removeRecipe($this);
        }
        return $this;
    }

    public function getRecipecategorys(): ?Collection
    {
        return $this->recipecategorys;
    }
    public function addComponent(Component $component): self
    {
        if (!$this->components->contains($component)) {
            $this->components->add($component);
            $component->addRecipe($this);
        }
        return $this;
    }

    public function removeComponent(Component $component): self
    {
        if ($this->components->removeElement($component)) {
            $component->removeRecipe($this);
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
           $recipestep->setRecipe($this);
        }
        return $this;
    }

    public function removeRecipeStep(RecipeStep $recipestep): self
    {
        if($this->recipesteps->removeElement($recipestep)) {
           if ($recipestep->getRecipe() === $this) {
               $recipestep->setRecipe(null);
           }
        }
        return $this;
    }

    public function getRecipesteps(): ?Collection
    {
        return $this->recipesteps;
    }

}
