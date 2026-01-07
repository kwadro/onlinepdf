<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\MegaMenuTranslationRepository;
use App\Entity\MegaMenuSetting;
use App\Entity\Locale;
use App\Entity\MegaMenuType;

#[ORM\Entity(repositoryClass: MegaMenuTranslationRepository::class)]
class MegaMenuTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: MegaMenuSetting::class,
            cascade: ['persist'],
            inversedBy: 'translations'
    )]
        private ?MegaMenuSetting $megamenusetting;
    #[ORM\ManyToOne(
            targetEntity: Locale::class,
            cascade: ['persist'],
            inversedBy: 'megamenutranslatelocales'
    )]
        private ?Locale $locale;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $status;
    #[ORM\ManyToOne(
            targetEntity: MegaMenuType::class,
            cascade: ['persist'],
            inversedBy: 'megamenutranslations'
    )]
        private ?MegaMenuType $megamenutype;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $position;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $url;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $content;

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

    public function getMegamenusetting(): ?MegaMenuSetting
    {
        return $this->megamenusetting;
    }
    public function setMegamenusetting(?MegaMenuSetting $megamenusetting): MegaMenuTranslation
    {
        $this->megamenusetting = $megamenusetting;
        return $this;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }
    public function setLocale(?Locale $locale): MegaMenuTranslation
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
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getMegamenutype(): ?MegaMenuType
    {
        return $this->megamenutype;
    }
    public function setMegamenutype(?MegaMenuType $megamenutype): MegaMenuTranslation
    {
        $this->megamenutype = $megamenutype;
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
    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

}