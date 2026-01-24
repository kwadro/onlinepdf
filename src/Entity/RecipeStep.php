<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RecipeStepRepository;
use App\Entity\RecipeTranslation;

#[ORM\Entity(repositoryClass: RecipeStepRepository::class)]
class RecipeStep
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

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
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