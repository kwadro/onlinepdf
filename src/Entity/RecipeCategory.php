<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeCategoryRepository;
use App\Entity\Recipe;

#[ORM\Entity(repositoryClass: RecipeCategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RecipeCategory
{
    use TimestampableTrait;
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
    private ?string $image;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $meta_title;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $meta_description;
    #[ORM\ManyToOne(
            targetEntity: self::class,
            cascade: ['persist'],
            inversedBy: 'children'
    )]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
        public ?RecipeCategory $parent=null;
    #[ORM\OneToMany(
        targetEntity: self::class,
        mappedBy: 'parent',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $children;
    #[ORM\ManyToMany(
          targetEntity: Recipe::class,
            inversedBy: 'recipecategorys',
            cascade: ['persist'],
            orphanRemoval: false,
    )]
        public ?Collection $recipes;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->recipes = new ArrayCollection();
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
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }
    public function setParent(?self $parent): RecipeCategory
    {
        $this->parent = $parent;
        return $this;
    }

    public function addChild(self $children): self
    {
        if(!$this->children->contains($children)) {
           $this->children[] = $children;
           $children->setParent($this);
        }
        return $this;
    }

    public function removeChild(self $children): self
    {
        if($this->children->removeElement($children)) {
           if ($children->getParent() === $this) {
               $children->setParent(null);
           }
        }
        return $this;
    }

    public function getChildren(): ?Collection
    {
        return $this->children;
    }
    public function addRecipe(Recipe $recipe): self
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes->add($recipe);
            $recipe->addRecipeCategory($this);
        }
        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->removeElement($recipe)) {
            $recipe->removeRecipeCategory($this);
        }
        return $this;
    }

    public function getRecipes(): ?Collection
    {
        return $this->recipes;
    }

}