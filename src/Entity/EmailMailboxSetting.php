<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmailMailboxSettingRepository;
use App\Entity\Site;
use App\Entity\EmailMessage;

#[ORM\Entity(repositoryClass: EmailMailboxSettingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailMailboxSetting
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'emailmailboxsettingsites'
    )]
        private ?Site $site;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxname;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxhost;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $boxport;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxusername;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxpassword;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxencryption;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxmailbox;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $boxactive;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $boxlast_uid;

    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $last_checked_at;
    #[ORM\OneToMany(
        targetEntity: EmailMessage::class,
        mappedBy: 'mailbox',
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
    public function setSite(?Site $site): EmailMailboxSetting
    {
        $this->site = $site;
        return $this;
    }
    public function setBoxname(?string $boxname): self
    {
        $this->boxname = $boxname;
        return $this;
    }

    public function getBoxname(): ?string
    {
        return $this->boxname;
    }
    public function __toString(): string
    {
        return $this->boxname;
    }
    public function setBoxhost(?string $boxhost): self
    {
        $this->boxhost = $boxhost;
        return $this;
    }

    public function getBoxhost(): ?string
    {
        return $this->boxhost;
    }
    public function setBoxport(?int $boxport): self
    {
        $this->boxport = $boxport;
        return $this;
    }

    public function getBoxport(): ?int
    {
        return $this->boxport;
    }
    public function setBoxusername(?string $boxusername): self
    {
        $this->boxusername = $boxusername;
        return $this;
    }

    public function getBoxusername(): ?string
    {
        return $this->boxusername;
    }
    public function setBoxpassword(?string $boxpassword): self
    {
        $this->boxpassword = $boxpassword;
        return $this;
    }

    public function getBoxpassword(): ?string
    {
        return $this->boxpassword;
    }
    public function setBoxencryption(?string $boxencryption): self
    {
        $this->boxencryption = $boxencryption;
        return $this;
    }

    public function getBoxencryption(): ?string
    {
        return $this->boxencryption;
    }
    public function setBoxmailbox(?string $boxmailbox): self
    {
        $this->boxmailbox = $boxmailbox;
        return $this;
    }

    public function getBoxmailbox(): ?string
    {
        return $this->boxmailbox;
    }
    public function setBoxactive(?string $boxactive): self
    {
        $this->boxactive = $boxactive;
        return $this;
    }

    public function getBoxactive(): ?string
    {
        return $this->boxactive;
    }
    public function setBoxlastUid(?int $boxlast_uid): self
    {
        $this->boxlast_uid = $boxlast_uid;
        return $this;
    }

    public function getBoxlastUid(): ?int
    {
        return $this->boxlast_uid;
    }
    public function setLastCheckedAt(?\DateTimeInterface $last_checked_at): self
    {
        $this->last_checked_at = $last_checked_at;
        return $this;
    }

    public function getLastCheckedAt(): ?\DateTimeInterface
    {
        return $this->last_checked_at;
    }

    public function addEmailMessage(EmailMessage $emailmessage): self
    {
        if(!$this->emailmessages->contains($emailmessage)) {
           $this->emailmessages[] = $emailmessage;
           $emailmessage->setMailbox($this);
        }
        return $this;
    }

    public function removeEmailMessage(EmailMessage $emailmessage): self
    {
        if($this->emailmessages->removeElement($emailmessage)) {
           if ($emailmessage->getMailbox() === $this) {
               $emailmessage->setMailbox(null);
           }
        }
        return $this;
    }

    public function getEmailmessages(): ?Collection
    {
        return $this->emailmessages;
    }

}
