<?php

namespace App\Entity;

use App\Repository\SamProjectRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SamProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SamProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;
    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created_at;
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updated_at;
    #[ORM\OneToMany(
        targetEntity: ServerData::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $servers;

    #[ORM\ManyToOne(inversedBy: 'samProjects')]
    private ?GitUser $git_user = null;

    #[ORM\Column(length: 255)]
    private ?string $git_url = null;

    /**
     * @var Collection<int, ServiceData>
     */
    #[ORM\OneToMany(
        targetEntity: ServiceData::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $services;
    #[ORM\OneToMany(
        targetEntity: UserAccess::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $users;

    public function __construct()
    {
        $this->servers = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }

    /** @return Collection<int, ServerData> */
    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function addServer(ServerData $server): self
    {
        if (!$this->servers->contains($server)) {
            $this->servers->add($server);
            $server->setProject($this);
        }
        return $this;
    }

    public function removeServer(ServerData $server): self
    {
        if ($this->servers->removeElement($server)) {
            if ($server->getProject() === $this) {
                $server->setProject(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getGitUser(): ?GitUser
    {
        return $this->git_user;
    }

    public function setGitUser(?GitUser $git_user): static
    {
        $this->git_user = $git_user;

        return $this;
    }

    public function getGitUrl(): ?string
    {
        return $this->git_url;
    }

    public function setGitUrl(string $git_url): static
    {
        $this->git_url = $git_url;

        return $this;
    }

    /**
     * @return Collection<int, ServiceData>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(ServiceData $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setProject($this);
        }

        return $this;
    }

    public function removeService(ServiceData $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getProject() === $this) {
                $service->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ServiceData>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUsers(UserAccess $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setProject($this);
        }

        return $this;
    }

    public function removeUsers(UserAccess $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getProject() === $this) {
                $user->setProject(null);
            }
        }

        return $this;
    }
}
