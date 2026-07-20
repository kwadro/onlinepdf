<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmailFilterRepository;
use App\Entity\EmailFilterGroup;
use App\Entity\EmailMessage;

#[ORM\Entity(repositoryClass: EmailFilterRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailFilter
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: EmailFilterGroup::class,
            cascade: ['persist'],
            inversedBy: 'emailfiltergroups'
    )]
        private ?EmailFilterGroup $filtergroup;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filtername;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filteremail;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $match_mode;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filteractive;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $filterlast_uid;
    #[ORM\OneToMany(
        targetEntity: EmailMessage::class,
        mappedBy: 'emailfilter',
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

    public function getFiltergroup(): ?EmailFilterGroup
    {
        return $this->filtergroup;
    }
    public function setFiltergroup(?EmailFilterGroup $filtergroup): EmailFilter
    {
        $this->filtergroup = $filtergroup;
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
    public function setFilteremail(?string $filteremail): self
    {
        $this->filteremail = $filteremail;
        return $this;
    }

    public function getFilteremail(): ?string
    {
        return $this->filteremail;
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
    public function setFilterlastUid(?int $filterlast_uid): self
    {
        $this->filterlast_uid = $filterlast_uid;
        return $this;
    }

    public function getFilterlastUid(): ?int
    {
        return $this->filterlast_uid;
    }

    public function addEmailMessage(EmailMessage $emailmessage): self
    {
        if(!$this->emailmessages->contains($emailmessage)) {
           $this->emailmessages[] = $emailmessage;
           $emailmessage->setEmailfilter($this);
        }
        return $this;
    }

    public function removeEmailMessage(EmailMessage $emailmessage): self
    {
        if($this->emailmessages->removeElement($emailmessage)) {
           if ($emailmessage->getEmailfilter() === $this) {
               $emailmessage->setEmailfilter(null);
           }
        }
        return $this;
    }

    public function getEmailmessages(): ?Collection
    {
        return $this->emailmessages;
    }

}
