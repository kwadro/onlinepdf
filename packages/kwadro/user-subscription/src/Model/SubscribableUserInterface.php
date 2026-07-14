<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Model;

use Doctrine\Common\Collections\Collection;
use Kwadro\UserSubscription\Entity\Subscription;

interface SubscribableUserInterface
{
    public function getId(): ?int;

    /** @return Collection<int, Subscription> */
    public function getSubscriptions(): Collection;

    public function addSubscription(Subscription $subscription): static;

    public function removeSubscription(Subscription $subscription): static;
}
