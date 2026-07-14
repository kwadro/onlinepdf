<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Kwadro\UserSubscription\Enum\BillingInterval;
use Kwadro\UserSubscription\Repository\SubscriptionPlanRepository;

#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plan')]
#[ORM\UniqueConstraint(name: 'uniq_subscription_plan_code', columns: ['code'])]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column(length: 3)]
    private string $currency = 'UAH';

    #[ORM\Column(name: 'billing_interval', length: 16, enumType: BillingInterval::class)]
    private BillingInterval $interval = BillingInterval::Monthly;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $features = [];

    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getInterval(): BillingInterval
    {
        return $this->interval;
    }

    public function setInterval(BillingInterval $interval): static
    {
        $this->interval = $interval;

        return $this;
    }

    /** @return list<string> */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /** @param list<string> $features */
    public function setFeatures(array $features): static
    {
        $this->features = array_values($features);

        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
