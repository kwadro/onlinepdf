<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\SiteRepository;
use App\Entity\HeaderSetting;
use App\Entity\SeoSetting;
use App\Entity\FooterSetting;
use App\Entity\MegaMenuSetting;
use App\Entity\Recipe;
use App\Entity\Popularsearch;
use App\Entity\FacebookSetting;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Site
{
    use TimestampableTrait;
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
    #[ORM\OneToMany(
        targetEntity: Recipe::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $recipesites;
    #[ORM\OneToMany(
        targetEntity: Popularsearch::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $popularsearchsites;
    #[ORM\OneToMany(
        targetEntity: FacebookSetting::class,
        mappedBy: 'site',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $facebooksettingsites;

    public function __construct()
    {
        $this->headersettingsites = new ArrayCollection();
        $this->seosettingsites = new ArrayCollection();
        $this->footersettingsites = new ArrayCollection();
        $this->megamenusites = new ArrayCollection();
        $this->recipesites = new ArrayCollection();
        $this->popularsearchsites = new ArrayCollection();
        $this->facebooksettingsites = new ArrayCollection();
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

    public function addRecipe(Recipe $recipe): self
    {
        if(!$this->recipesites->contains($recipe)) {
           $this->recipesites[] = $recipe;
           $recipe->setSite($this);
        }
        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if($this->recipesites->removeElement($recipe)) {
           if ($recipe->getSite() === $this) {
               $recipe->setSite(null);
           }
        }
        return $this;
    }

    public function getRecipesites(): ?Collection
    {
        return $this->recipesites;
    }

    public function addPopularsearch(Popularsearch $popularsearch): self
    {
        if(!$this->popularsearchsites->contains($popularsearch)) {
           $this->popularsearchsites[] = $popularsearch;
           $popularsearch->setSite($this);
        }
        return $this;
    }

    public function removePopularsearch(Popularsearch $popularsearch): self
    {
        if($this->popularsearchsites->removeElement($popularsearch)) {
           if ($popularsearch->getSite() === $this) {
               $popularsearch->setSite(null);
           }
        }
        return $this;
    }

    public function getPopularsearchsites(): ?Collection
    {
        return $this->popularsearchsites;
    }

    public function addFacebookSetting(FacebookSetting $facebooksetting): self
    {
        if(!$this->facebooksettingsites->contains($facebooksetting)) {
           $this->facebooksettingsites[] = $facebooksetting;
           $facebooksetting->setSite($this);
        }
        return $this;
    }

    public function removeFacebookSetting(FacebookSetting $facebooksetting): self
    {
        if($this->facebooksettingsites->removeElement($facebooksetting)) {
           if ($facebooksetting->getSite() === $this) {
               $facebooksetting->setSite(null);
           }
        }
        return $this;
    }

    public function getFacebooksettingsites(): ?Collection
    {
        return $this->facebooksettingsites;
    }

}
