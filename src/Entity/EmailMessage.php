<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Entity\Traits\TimeStampAbleTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmailMessageRepository;
use App\Entity\Site;
use App\Entity\EmailMailboxSetting;
use App\Entity\EmailSenderFilter;

#[ORM\Entity(repositoryClass: EmailMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailMessage
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $id;
    #[ORM\ManyToOne(
            targetEntity: Site::class,
            cascade: ['persist'],
            inversedBy: 'emailmessagesites'
    )]
        private ?Site $site;
    #[ORM\ManyToOne(
            targetEntity: EmailMailboxSetting::class,
            cascade: ['persist'],
            inversedBy: 'emailmessages'
    )]
        private ?EmailMailboxSetting $mailbox;
    #[ORM\ManyToOne(
            targetEntity: EmailSenderFilter::class,
            cascade: ['persist'],
            inversedBy: 'emailmessages'
    )]
        private ?EmailSenderFilter $sender_filter;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $imap_uid;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $message_id;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $from_address;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $from_name;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $recipient;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $subject;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $body_html;

    #[ORM\Column(type:"datetime_immutable", nullable:true)]
    private ?\DateTimeImmutable $received_at;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $is_seen;

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
    public function setSite(?Site $site): EmailMessage
    {
        $this->site = $site;
        return $this;
    }

    public function getMailbox(): ?EmailMailboxSetting
    {
        return $this->mailbox;
    }
    public function setMailbox(?EmailMailboxSetting $mailbox): EmailMessage
    {
        $this->mailbox = $mailbox;
        return $this;
    }

    public function getSenderFilter(): ?EmailSenderFilter
    {
        return $this->sender_filter;
    }
    public function setSenderFilter(?EmailSenderFilter $sender_filter): EmailMessage
    {
        $this->sender_filter = $sender_filter;
        return $this;
    }
    public function setImapUid(?int $imap_uid): self
    {
        $this->imap_uid = $imap_uid;
        return $this;
    }

    public function getImapUid(): ?int
    {
        return $this->imap_uid;
    }
    public function setMessageId(?string $message_id): self
    {
        $this->message_id = $message_id;
        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->message_id;
    }
    public function setFromAddress(?string $from_address): self
    {
        $this->from_address = $from_address;
        return $this;
    }

    public function getFromAddress(): ?string
    {
        return $this->from_address;
    }
    public function __toString(): string
    {
        return $this->from_address;
    }
    public function setFromName(?string $from_name): self
    {
        $this->from_name = $from_name;
        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->from_name;
    }
    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }
    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }
    public function setBodyHtml(?string $body_html): self
    {
        $this->body_html = $body_html;
        return $this;
    }

    public function getBodyHtml(): ?string
    {
        return $this->body_html;
    }
    public function setReceivedAt(?\DateTimeImmutable $received_at): self
    {
        $this->received_at = $received_at;
        return $this;
    }

    public function getReceivedAt(): ?\DateTimeImmutable
    {
        return $this->received_at;
    }
    public function setIsSeen(?string $is_seen): self
    {
        $this->is_seen = $is_seen;
        return $this;
    }

    public function getIsSeen(): ?string
    {
        return $this->is_seen;
    }

}
