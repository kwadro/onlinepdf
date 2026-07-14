<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kwadro\UserSubscription\Entity\Subscription;

trait SubscribableUserTrait
{
    /** @var Collection<int, Subscription> */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: false)]
    private Collection $subscriptions;

    /** @return Collection<int, Subscription> */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions ??= new ArrayCollection();
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->getSubscriptions()->contains($subscription)) {
            $this->getSubscriptions()->add($subscription);
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->getSubscriptions()->removeElement($subscription)) {
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }
}
