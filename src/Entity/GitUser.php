<?php

namespace App\Entity;

use App\Repository\GitUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GitUserRepository::class)]
class GitUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $user_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $user_password = null;

    /**
     * @var Collection<int, SamProject>
     */
    #[ORM\OneToMany(targetEntity: SamProject::class, mappedBy: 'git_user')]
    private Collection $samProjects;

    public function __construct()
    {
        $this->samProjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserName(): ?string
    {
        return $this->user_name;
    }

    public function setUserName(?string $user_name): static
    {
        $this->user_name = $user_name;

        return $this;
    }

    public function getUserPassword(): ?string
    {
        return $this->user_password;
    }

    public function setUserPassword(?string $user_password): static
    {
        $this->user_password = $user_password;

        return $this;
    }

    /**
     * @return Collection<int, SamProject>
     */
    public function getSamProjects(): Collection
    {
        return $this->samProjects;
    }

    public function addSamProject(SamProject $samProject): static
    {
        if (!$this->samProjects->contains($samProject)) {
            $this->samProjects->add($samProject);
            $samProject->setGitUser($this);
        }

        return $this;
    }

    public function removeSamProject(SamProject $samProject): static
    {
        if ($this->samProjects->removeElement($samProject)) {
            // set the owning side to null (unless already changed)
            if ($samProject->getGitUser() === $this) {
                $samProject->setGitUser(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->user_name . "|" . $this->user_password;
    }
}
