<?php

namespace App\Entity;

use App\Repository\SamProjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SamProjectRepository::class)]
class SamProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;
    #[ORM\Column]
    private ?int $typeServer = null;
    #[ORM\Column(length: 255)]
    private ?string $host = null;
    #[ORM\Column(length: 255)]
    private ?string $port = null;
    #[ORM\Column(length: 255)]
    private ?string $user = null;
    #[ORM\Column(length: 255)]
    private ?string $password = null;
    #[ORM\Column(length: 255)]
    private ?string $dumpLink = null;
    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function getTypeServer(): ?int
    {
        return $this->typeServer;
    }
    public function getHost(): ?string
    {
        return $this->host;
    }
    public function getPort(): ?string
    {
        return $this->port;
    }
    public function getUser(): ?string
    {
        return $this->host;
    }
    public function getPassword(): ?string
    {
        return $this->host;
    }
    public function getDumpLink(): ?string
    {
        return $this->dumpLink;
    }
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static
    {
        if(!$created_at){
            $created_at = new \DateTimeImmutable();
        }
        $this->created_at = $created_at;

        return $this;
    }
    public function setTypeServer(?int$typeServer): static
    {
        $this->typeServer=$typeServer;
        return $this;
    }
    public function setHost(?string $host): static
    {
        $this->host = $host;
        return $this;
    }
    public function setPort(?string $port): static
    {
        $this->port = $port;
        return $this;
    }
    public function setUser(?string $user): static
    {
        $this->user = $user;
        return $this;
    }
    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }
    public function setDumpLink(?string $dumpLink): static
    {
        $this->dumpLink = $dumpLink;
        return $this;
    }
}
