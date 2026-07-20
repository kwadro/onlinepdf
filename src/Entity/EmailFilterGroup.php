<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmailFilterGroupRepository;
use App\Entity\EmailMailbox;
use App\Entity\EmailFilter;

#[ORM\Entity(repositoryClass: EmailFilterGroupRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailFilterGroup
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: EmailMailbox::class,
            cascade: ['persist'],
            inversedBy: 'filtergroups'
    )]
        private ?EmailMailbox $mailbox;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filtergroupname;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $filtergroupactive;
    #[ORM\OneToMany(
        targetEntity: EmailFilter::class,
        mappedBy: 'filtergroup',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $emailfilters;

    public function __construct()
    {
        $this->emailfilters = new ArrayCollection();
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

    public function getMailbox(): ?EmailMailbox
    {
        return $this->mailbox;
    }
    public function setMailbox(?EmailMailbox $mailbox): EmailFilterGroup
    {
        $this->mailbox = $mailbox;
        return $this;
    }
    public function setFiltergroupname(?string $filtergroupname): self
    {
        $this->filtergroupname = $filtergroupname;
        return $this;
    }

    public function getFiltergroupname(): ?string
    {
        return $this->filtergroupname;
    }
    public function __toString(): string
    {
        return $this->filtergroupname;
    }
    public function setFiltergroupactive(?string $filtergroupactive): self
    {
        $this->filtergroupactive = $filtergroupactive;
        return $this;
    }

    public function getFiltergroupactive(): ?string
    {
        return $this->filtergroupactive;
    }

    public function addEmailFilter(EmailFilter $emailfilter): self
    {
        if(!$this->emailfilters->contains($emailfilter)) {
           $this->emailfilters[] = $emailfilter;
           $emailfilter->setFiltergroup($this);
        }
        return $this;
    }

    public function removeEmailFilter(EmailFilter $emailfilter): self
    {
        if($this->emailfilters->removeElement($emailfilter)) {
           if ($emailfilter->getFiltergroup() === $this) {
               $emailfilter->setFiltergroup(null);
           }
        }
        return $this;
    }

    public function getEmailfilters(): ?Collection
    {
        return $this->emailfilters;
    }

}
