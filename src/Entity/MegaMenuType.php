<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\MegaMenuTypeRepository;
use App\Entity\MegaMenuTranslation;

#[ORM\Entity(repositoryClass: MegaMenuTypeRepository::class)]
class MegaMenuType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;
    #[ORM\OneToMany(
        targetEntity: MegaMenuTranslation::class,
        mappedBy: 'megamenutype',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $megamenutranslations;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->megamenutranslations = new ArrayCollection();
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

    public function addMegaMenuTranslation(MegaMenuTranslation $megamenutranslation): self
    {
        if(!$this->megamenutranslations->contains($megamenutranslation)) {
           $this->megamenutranslations[] = $megamenutranslation;
           $megamenutranslation->setMegamenutype($this);
        }
        return $this;
    }

    public function removeMegaMenuTranslation(MegaMenuTranslation $megamenutranslation): self
    {
        if($this->megamenutranslations->removeElement($megamenutranslation)) {
           if ($megamenutranslation->getMegamenutype() === $this) {
               $megamenutranslation->setMegamenutype(null);
           }
        }
        return $this;
    }

    public function getMegamenutranslations(): ?Collection
    {
        return $this->megamenutranslations;
    }

}