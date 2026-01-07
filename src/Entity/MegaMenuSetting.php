<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\MegaMenuSettingRepository;
use App\Entity\Site;
use App\Entity\MegaMenuTranslation;

#[ORM\Entity(repositoryClass: MegaMenuSettingRepository::class)]
class MegaMenuSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'megamenusites'
    )]
        private ?Site $site;
    #[ORM\OneToMany(
        targetEntity: MegaMenuTranslation::class,
        mappedBy: 'megamenusetting',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $translations;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
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
    public function setSite(?Site $site): MegaMenuSetting
    {
        $this->site = $site;
        return $this;
    }
    public function __toString(): string
    {
        return $this->site;
    }

    public function addMegaMenuTranslation(MegaMenuTranslation $megamenutranslation): self
    {
        if(!$this->translations->contains($megamenutranslation)) {
           $this->translations[] = $megamenutranslation;
           $megamenutranslation->setMegamenusetting($this);
        }
        return $this;
    }

    public function removeMegaMenuTranslation(MegaMenuTranslation $megamenutranslation): self
    {
        if($this->translations->removeElement($megamenutranslation)) {
           if ($megamenutranslation->getMegamenusetting() === $this) {
               $megamenutranslation->setMegamenusetting(null);
           }
        }
        return $this;
    }

    public function getTranslations(): ?Collection
    {
        return $this->translations;
    }

}