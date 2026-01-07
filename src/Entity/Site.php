<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\SiteRepository;
use App\Entity\HeaderSetting;
use App\Entity\SeoSetting;
use App\Entity\FooterSetting;
use App\Entity\MegaMenuSetting;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $code;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $domain;
    #[ORM\OneToMany(
        targetEntity: HeaderSetting::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $headersettingsites;
    #[ORM\OneToMany(
        targetEntity: SeoSetting::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $seosettingsites;
    #[ORM\OneToMany(
        targetEntity: FooterSetting::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $footersettingsites;
    #[ORM\OneToMany(
        targetEntity: MegaMenuSetting::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $megamenusites;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $created_at;

    public function __construct()
    {
        $this->headersettingsites = new ArrayCollection();
        $this->seosettingsites = new ArrayCollection();
        $this->footersettingsites = new ArrayCollection();
        $this->megamenusites = new ArrayCollection();
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
    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }
    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }
    public function __toString(): string
    {
        return $this->domain;
    }

    public function addHeaderSetting(HeaderSetting $headersetting): self
    {
        if(!$this->headersettingsites->contains($headersetting)) {
           $this->headersettingsites[] = $headersetting;
           $headersetting->setSite($this);
        }
        return $this;
    }

    public function removeHeaderSetting(HeaderSetting $headersetting): self
    {
        if($this->headersettingsites->removeElement($headersetting)) {
           if ($headersetting->getSite() === $this) {
               $headersetting->setSite(null);
           }
        }
        return $this;
    }

    public function getHeadersettingsites(): ?Collection
    {
        return $this->headersettingsites;
    }

    public function addSeoSetting(SeoSetting $seosetting): self
    {
        if(!$this->seosettingsites->contains($seosetting)) {
           $this->seosettingsites[] = $seosetting;
           $seosetting->setSite($this);
        }
        return $this;
    }

    public function removeSeoSetting(SeoSetting $seosetting): self
    {
        if($this->seosettingsites->removeElement($seosetting)) {
           if ($seosetting->getSite() === $this) {
               $seosetting->setSite(null);
           }
        }
        return $this;
    }

    public function getSeosettingsites(): ?Collection
    {
        return $this->seosettingsites;
    }

    public function addFooterSetting(FooterSetting $footersetting): self
    {
        if(!$this->footersettingsites->contains($footersetting)) {
           $this->footersettingsites[] = $footersetting;
           $footersetting->setSite($this);
        }
        return $this;
    }

    public function removeFooterSetting(FooterSetting $footersetting): self
    {
        if($this->footersettingsites->removeElement($footersetting)) {
           if ($footersetting->getSite() === $this) {
               $footersetting->setSite(null);
           }
        }
        return $this;
    }

    public function getFootersettingsites(): ?Collection
    {
        return $this->footersettingsites;
    }

    public function addMegaMenuSetting(MegaMenuSetting $megamenusetting): self
    {
        if(!$this->megamenusites->contains($megamenusetting)) {
           $this->megamenusites[] = $megamenusetting;
           $megamenusetting->setSite($this);
        }
        return $this;
    }

    public function removeMegaMenuSetting(MegaMenuSetting $megamenusetting): self
    {
        if($this->megamenusites->removeElement($megamenusetting)) {
           if ($megamenusetting->getSite() === $this) {
               $megamenusetting->setSite(null);
           }
        }
        return $this;
    }

    public function getMegamenusites(): ?Collection
    {
        return $this->megamenusites;
    }

}