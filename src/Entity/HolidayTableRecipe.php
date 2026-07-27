<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\HolidayTableRecipeRepository;
use App\Entity\HolidayTable;
use App\Entity\Recipe;

#[ORM\Entity(repositoryClass: HolidayTableRecipeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HolidayTableRecipe
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: HolidayTable::class,
            cascade: ['persist'],
            inversedBy: 'holidaytablerecipes'
    )]
        private ?HolidayTable $holidaytable;
    #[ORM\ManyToOne(
            targetEntity: Recipe::class,
            cascade: ['persist'],
            inversedBy: ''
    )]
        private ?Recipe $recipe;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;

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

    public function getHolidaytable(): ?HolidayTable
    {
        return $this->holidaytable;
    }
    public function setHolidaytable(?HolidayTable $holidaytable): HolidayTableRecipe
    {
        $this->holidaytable = $holidaytable;
        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }
    public function setRecipe(?Recipe $recipe): HolidayTableRecipe
    {
        $this->recipe = $recipe;
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

}
