<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\HolidayTableRepository;
use App\Entity\User;
use App\Entity\Site;
use App\Entity\Locale;
use App\Entity\HolidayTableRecipe;

#[ORM\Entity(repositoryClass: HolidayTableRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HolidayTable
{
    use TimeStampAbleTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: User::class,
            cascade: ['persist'],
            inversedBy: ''
    )]
        private ?User $user;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: ''
    )]
        private ?Site $site;
    #[ORM\ManyToOne(
            targetEntity: Locale::class,
            cascade: ['persist'],
            inversedBy: ''
    )]
        private ?Locale $locale;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $guest_count;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $event_name;

    #[ORM\Column(type:"date_immutable", nullable:true)]
    private ?\DateTimeImmutable $event_date;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $men_count;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $women_count;
    #[ORM\OneToMany(
        targetEntity: HolidayTableRecipe::class,
        mappedBy: 'holidaytable',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $holidaytablerecipes;

    public function __construct()
    {
        $this->holidaytablerecipes = new ArrayCollection();
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

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): HolidayTable
    {
        $this->user = $user;
        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }
    public function setSite(?Site $site): HolidayTable
    {
        $this->site = $site;
        return $this;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }
    public function setLocale(?Locale $locale): HolidayTable
    {
        $this->locale = $locale;
        return $this;
    }
    public function setGuestCount(?int $guest_count): self
    {
        $this->guest_count = $guest_count;
        return $this;
    }

    public function getGuestCount(): ?int
    {
        return $this->guest_count;
    }

    public function setEventName(?string $event_name): self
    {
        $this->event_name = $event_name;

        return $this;
    }

    public function getEventName(): ?string
    {
        return $this->event_name;
    }

    public function setEventDate(?\DateTimeImmutable $event_date): self
    {
        $this->event_date = $event_date;

        return $this;
    }

    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->event_date;
    }

    public function setMenCount(?int $men_count): self
    {
        $this->men_count = $men_count;
        return $this;
    }

    public function getMenCount(): ?int
    {
        return $this->men_count;
    }
    public function setWomenCount(?int $women_count): self
    {
        $this->women_count = $women_count;
        return $this;
    }

    public function getWomenCount(): ?int
    {
        return $this->women_count;
    }

    public function addHolidayTableRecipe(HolidayTableRecipe $holidaytablerecipe): self
    {
        if(!$this->holidaytablerecipes->contains($holidaytablerecipe)) {
           $this->holidaytablerecipes[] = $holidaytablerecipe;
           $holidaytablerecipe->setHolidaytable($this);
        }
        return $this;
    }

    public function removeHolidayTableRecipe(HolidayTableRecipe $holidaytablerecipe): self
    {
        if($this->holidaytablerecipes->removeElement($holidaytablerecipe)) {
           if ($holidaytablerecipe->getHolidaytable() === $this) {
               $holidaytablerecipe->setHolidaytable(null);
           }
        }
        return $this;
    }

    public function getHolidaytablerecipes(): ?Collection
    {
        return $this->holidaytablerecipes;
    }

}
