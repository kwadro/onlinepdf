<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeRepository;
use App\Entity\Site;
use App\Entity\RecipeCategory;
use App\Entity\RecipeTranslation;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'recipesites'
    )]
        private ?Site $site;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $prep_time_min;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $cook_time_min;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $servings;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $image;
    #[ORM\ManyToMany(
          targetEntity: RecipeCategory::class,
            mappedBy: 'recipes',
            cascade: ['persist'],
            orphanRemoval: false,
    )]
        public ?Collection $recipecategorys;
    #[ORM\OneToMany(
        targetEntity: RecipeTranslation::class,
        mappedBy: 'recipe',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $recipetranslations;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->recipecategorys = new ArrayCollection();
        $this->recipetranslations = new ArrayCollection();
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

    public function getSite(): ?Site
    {
        return $this->site;
    }
    public function setSite(?Site $site): Recipe
    {
        $this->site = $site;
        return $this;
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
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
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

    public function addRecipeTranslation(RecipeTranslation $recipetranslation): self
    {
        if(!$this->recipetranslations->contains($recipetranslation)) {
           $this->recipetranslations[] = $recipetranslation;
           $recipetranslation->setRecipe($this);
        }
        return $this;
    }

    public function removeRecipeTranslation(RecipeTranslation $recipetranslation): self
    {
        if($this->recipetranslations->removeElement($recipetranslation)) {
           if ($recipetranslation->getRecipe() === $this) {
               $recipetranslation->setRecipe(null);
           }
        }
        return $this;
    }

    public function getRecipetranslations(): ?Collection
    {
        return $this->recipetranslations;
    }
    public function __toString(): string
    {
       return $this->site . '|' . $this->id;
    }

}