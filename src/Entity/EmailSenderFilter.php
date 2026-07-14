<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmailSenderFilterRepository;
use App\Entity\Site;
use App\Entity\EmailMessage;

#[ORM\Entity(repositoryClass: EmailSenderFilterRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailSenderFilter
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'emailsenderfiltersites'
    )]
        private ?Site $site;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filtername;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filtersender;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $match_mode;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filteractive;
    #[ORM\OneToMany(
        targetEntity: EmailMessage::class,
        mappedBy: 'sender_filter',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $emailmessages;

    public function __construct()
    {
        $this->emailmessages = new ArrayCollection();
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
    public function setSite(?Site $site): EmailSenderFilter
    {
        $this->site = $site;
        return $this;
    }
    public function setFiltername(?string $filtername): self
    {
        $this->filtername = $filtername;
        return $this;
    }

    public function getFiltername(): ?string
    {
        return $this->filtername;
    }
    public function __toString(): string
    {
        return $this->filtername;
    }
    public function setFiltersender(?string $filtersender): self
    {
        $this->filtersender = $filtersender;
        return $this;
    }

    public function getFiltersender(): ?string
    {
        return $this->filtersender;
    }
    public function setMatchMode(?string $match_mode): self
    {
        $this->match_mode = $match_mode;
        return $this;
    }

    public function getMatchMode(): ?string
    {
        return $this->match_mode;
    }
    public function setFilteractive(?string $filteractive): self
    {
        $this->filteractive = $filteractive;
        return $this;
    }

    public function getFilteractive(): ?string
    {
        return $this->filteractive;
    }

    public function addEmailMessage(EmailMessage $emailmessage): self
    {
        if(!$this->emailmessages->contains($emailmessage)) {
           $this->emailmessages[] = $emailmessage;
           $emailmessage->setSenderFilter($this);
        }
        return $this;
    }

    public function removeEmailMessage(EmailMessage $emailmessage): self
    {
        if($this->emailmessages->removeElement($emailmessage)) {
           if ($emailmessage->getSenderFilter() === $this) {
               $emailmessage->setSenderFilter(null);
           }
        }
        return $this;
    }

    public function getEmailmessages(): ?Collection
    {
        return $this->emailmessages;
    }

}
