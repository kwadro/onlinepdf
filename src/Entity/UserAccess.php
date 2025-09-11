<?php

namespace App\Entity;

use App\Repository\UserAccessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAccessRepository::class)]
class UserAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $serverType = [];

    #[ORM\ManyToOne(inversedBy: 'accesses')]
    private ?User $user = null;

    #[ORM\ManyToOne(
        cascade: ['persist', 'remove'],
        inversedBy: 'users'
    )]
    private ?SamProject $project = null;
    #[ORM\Column]
    private bool $service = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServerType(): array
    {
        return $this->serverType;
    }

    public function setServerType(array $serverType): static
    {
        $this->serverType = $serverType;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Access %s', $this->project);
    }

    public function getProject(): ?SamProject
    {
        return $this->project;
    }

    public function setProject(?SamProject $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getService(): bool
    {
        return $this->service;
    }

    public function setService(bool $service): static
    {
        $this->service = $service;

        return $this;
    }

}
