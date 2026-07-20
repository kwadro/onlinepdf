<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmailMailboxFolderRepository;
use App\Entity\EmailMailbox;
use App\Entity\EmailMessage;

#[ORM\Entity(repositoryClass: EmailMailboxFolderRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailMailboxFolder
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: EmailMailbox::class,
            cascade: ['persist'],
            inversedBy: 'folders'
    )]
        private ?EmailMailbox $emailmailbox;
    #[ORM\OneToMany(
        targetEntity: EmailMessage::class,
        mappedBy: 'mailboxfolder',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
        public ?Collection $emailmessages;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $foldername;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $folderactive;

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

    public function getEmailmailbox(): ?EmailMailbox
    {
        return $this->emailmailbox;
    }
    public function setEmailmailbox(?EmailMailbox $emailmailbox): EmailMailboxFolder
    {
        $this->emailmailbox = $emailmailbox;
        return $this;
    }

    public function addEmailMessage(EmailMessage $emailmessage): self
    {
        if(!$this->emailmessages->contains($emailmessage)) {
           $this->emailmessages[] = $emailmessage;
           $emailmessage->setMailboxfolder($this);
        }
        return $this;
    }

    public function removeEmailMessage(EmailMessage $emailmessage): self
    {
        if($this->emailmessages->removeElement($emailmessage)) {
           if ($emailmessage->getMailboxfolder() === $this) {
               $emailmessage->setMailboxfolder(null);
           }
        }
        return $this;
    }

    public function getEmailmessages(): ?Collection
    {
        return $this->emailmessages;
    }
    public function setFoldername(?string $foldername): self
    {
        $this->foldername = $foldername;
        return $this;
    }

    public function getFoldername(): ?string
    {
        return $this->foldername;
    }
    public function __toString(): string
    {
        return $this->foldername;
    }
    public function setFolderactive(?string $folderactive): self
    {
        $this->folderactive = $folderactive;
        return $this;
    }

    public function getFolderactive(): ?string
    {
        return $this->folderactive;
    }

}
