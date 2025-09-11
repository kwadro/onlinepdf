<?php

namespace App\Entity;

use App\Repository\UserProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProjectRepository::class)]
class UserProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, SamProject>
     */
    #[ORM\ManyToMany(targetEntity: SamProject::class, inversedBy: 'users')]
    private Collection $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, SamProject>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(SamProject $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
        }

        return $this;
    }

    public function removeProject(SamProject $project): static
    {
        $this->projects->removeElement($project);

        return $this;
    }
}
