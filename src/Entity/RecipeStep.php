<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeStepRepository;
use App\Entity\RecipeTranslation;

#[ORM\Entity(repositoryClass: RecipeStepRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RecipeStep
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;
    #[ORM\ManyToOne(
            targetEntity: RecipeTranslation::class,
            cascade: ['persist'],
            inversedBy: 'recipesteps'
    )]
        private ?RecipeTranslation $recipetranslation;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $question;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $answer;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $image;

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
    public function setRecipetranslation(?RecipeTranslation $recipetranslation): RecipeStep
    {
        $this->recipetranslation = $recipetranslation;
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
    public function setQuestion(?string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }
    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;
        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
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

}