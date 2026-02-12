<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\PopularsearchRepository;
use App\Entity\Site;
use App\Entity\Locale;

#[ORM\Entity(repositoryClass: PopularsearchRepository::class)]
class Popularsearch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $image;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'popularsearchsites'
    )]
        private ?Site $site;
    #[ORM\ManyToOne(
            targetEntity: Locale::class,
            cascade: ['persist'],
            inversedBy: 'Popularsearchlocales'
    )]
        private ?Locale $locale;

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
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }
    public function setSite(?Site $site): Popularsearch
    {
        $this->site = $site;
        return $this;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }
    public function setLocale(?Locale $locale): Popularsearch
    {
        $this->locale = $locale;
        return $this;
    }

}