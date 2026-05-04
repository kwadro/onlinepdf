<?php

namespace App\Entity;

use App\Entity\Traits\TimeStampAbleTrait;
use App\Repository\FavoriteListRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteListRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FavoriteList
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $id;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $user_id;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $recipe_id;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $site_id;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $locale_id;

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

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
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

    public function setSiteId(?int $site_id): self
    {
        $this->site_id = $site_id;
        return $this;
    }

    public function getSiteId(): ?int
    {
        return $this->site_id;
    }

    public function setLocaleId(?int $locale_id): self
    {
        $this->locale_id = $locale_id;
        return $this;
    }

    public function getLocaleId(): ?int
    {
        return $this->locale_id;
    }
}
