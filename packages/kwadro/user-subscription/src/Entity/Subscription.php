<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Kwadro\UserSubscription\Enum\SubscriptionStatus;
use Kwadro\UserSubscription\Model\SubscribableUserInterface;
use Kwadro\UserSubscription\Repository\SubscriptionRepository;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'user_subscription')]
#[ORM\Index(name: 'idx_user_subscription_status', columns: ['status'])]
#[ORM\Index(name: 'idx_user_subscription_expires_at', columns: ['expires_at'])]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SubscribableUserInterface::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SubscribableUserInterface $user = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(length: 16, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status = SubscriptionStatus::Pending;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $renewedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?SubscribableUserInterface
    {
        return $this->user;
    }

    public function setUser(?SubscribableUserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getRenewedAt(): ?\DateTimeImmutable
    {
        return $this->renewedAt;
    }

    public function setRenewedAt(?\DateTimeImmutable $renewedAt): static
    {
        $this->renewedAt = $renewedAt;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed> $metadata */
    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function isCurrentlyActive(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        if ($this->status !== SubscriptionStatus::Active) {
            return false;
        }

        if ($this->expiresAt !== null && $this->expiresAt <= $now) {
            return false;
        }

        return true;
    }
}
