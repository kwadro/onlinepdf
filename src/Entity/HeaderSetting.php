<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\HeaderSettingRepository;
use App\Entity\Site;
use App\Entity\HeaderTranslation;

#[ORM\Entity(repositoryClass: HeaderSettingRepository::class)]
class HeaderSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'headersettingsites'
    )]
        private ?Site $site;
    #[ORM\OneToMany(
        targetEntity: HeaderTranslation::class,
        mappedBy: 'headersetting',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $translations;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $logo;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $favicon;

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
    public function setSite(?Site $site): HeaderSetting
    {
        $this->site = $site;
        return $this;
    }
    public function __toString(): string
    {
        return $this->site;
    }

    public function addHeaderTranslation(HeaderTranslation $headertranslation): self
    {
        if(!$this->translations->contains($headertranslation)) {
           $this->translations[] = $headertranslation;
           $headertranslation->setHeadersetting($this);
        }
        return $this;
    }

    public function removeHeaderTranslation(HeaderTranslation $headertranslation): self
    {
        if($this->translations->removeElement($headertranslation)) {
           if ($headertranslation->getHeadersetting() === $this) {
               $headertranslation->setHeadersetting(null);
           }
        }
        return $this;
    }

    public function getTranslations(): ?Collection
    {
        return $this->translations;
    }
    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }
    public function setFavicon(?string $favicon): self
    {
        $this->favicon = $favicon;
        return $this;
    }

    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

}