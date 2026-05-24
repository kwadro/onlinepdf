<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\FacebookSettingRepository;
use App\Entity\Site;
use App\Entity\Locale;

#[ORM\Entity(repositoryClass: FacebookSettingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FacebookSetting
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'facebooksettingsites'
    )]
        private ?Site $site;
    #[ORM\ManyToOne(
            targetEntity: Locale::class,
            cascade: ['persist'],
            inversedBy: 'facebooksettinglocales'
    )]
        private ?Locale $locale;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $recipe_id;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $text_post;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $tag;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $title1;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $title2;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $title3;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $content1;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $content2;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $content3;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $content4;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $notes;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $template;

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

    public function getSite(): ?Site
    {
        return $this->site;
    }
    public function setSite(?Site $site): FacebookSetting
    {
        $this->site = $site;
        return $this;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }
    public function setLocale(?Locale $locale): FacebookSetting
    {
        $this->locale = $locale;
        return $this;
    }
    public function setRecipeId(?int $recipe_id): self
    {
        $this->recipe_id = $recipe_id;
        return $this;
    }

    public function getRecipeId(): ?int
    {
        return $this->recipe_id;
    }
    public function __toString(): string
    {
        return $this->recipe_id;
    }
    public function setTextPost(?string $text_post): self
    {
        $this->text_post = $text_post;
        return $this;
    }

    public function getTextPost(): ?string
    {
        return $this->text_post;
    }
    public function setTag(?string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }
    public function setTitle1(?string $title1): self
    {
        $this->title1 = $title1;
        return $this;
    }

    public function getTitle1(): ?string
    {
        return $this->title1;
    }
    public function setTitle2(?string $title2): self
    {
        $this->title2 = $title2;
        return $this;
    }

    public function getTitle2(): ?string
    {
        return $this->title2;
    }
    public function setTitle3(?string $title3): self
    {
        $this->title3 = $title3;
        return $this;
    }

    public function getTitle3(): ?string
    {
        return $this->title3;
    }
    public function setContent1(?string $content1): self
    {
        $this->content1 = $content1;
        return $this;
    }

    public function getContent1(): ?string
    {
        return $this->content1;
    }
    public function setContent2(?string $content2): self
    {
        $this->content2 = $content2;
        return $this;
    }

    public function getContent2(): ?string
    {
        return $this->content2;
    }
    public function setContent3(?string $content3): self
    {
        $this->content3 = $content3;
        return $this;
    }

    public function getContent3(): ?string
    {
        return $this->content3;
    }
    public function setContent4(?string $content4): self
    {
        $this->content4 = $content4;
        return $this;
    }

    public function getContent4(): ?string
    {
        return $this->content4;
    }
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function setTemplate(?int $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplate(): ?int
    {
        return $this->template;
    }

}