<?php

namespace App\Entity;

use App\Repository\ServerDataRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServerDataRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ServerData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $hostname = null;

    #[ORM\Column(length: 255)]
    private ?string $port = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;
    #[ORM\Column(length: 255)]
    private ?string $password = null;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $dump_link = null;
    #[ORM\Column]
    private ?int $type_server = null;
    #[ORM\Column]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updated_at = null;
    /**
     * Many Servers belong to one Project.
     * This is the owning side (it holds the foreign key).
     */
    #[ORM\ManyToOne(
        cascade: ['persist', 'remove'],
        inversedBy: 'servers'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?SamProject $project = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $web_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $web_admin_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $web_admin_login = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $web_admin_password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $http_auth_login = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $http_auth_password = null;
    #[ORM\Column(length: 255)]
    private ?string $php_version = null;

    #[ORM\Column(length: 255)]
    private ?string $framework_version = null;
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

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(string $hostname): static
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getPort(): ?string
    {
        return $this->port;
    }

    public function setPort(string $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getDumpLink(): ?string
    {
        return $this->dump_link;
    }

    public function setDumpLink(?string $dump_link): static
    {
        $this->dump_link = $dump_link;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getTypeServer(): ?int
    {
        return $this->type_server;
    }

    public function setTypeServer(int $type_server): static
    {
        $this->type_server = $type_server;

        return $this;
    }

    public function getProject(): ?SamProject
    {
        return $this->project;
    }

    public function setProject(?SamProject $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function __toString(): string
    {
        $options = [
            1 => 'Live',
            2 => 'Test',
            3 => 'Stage',
        ];

        return $options[$this->type_server] ?? 0;
    }

    public function getWebUrl(): ?string
    {
        return $this->web_url;
    }

    public function setWebUrl(?string $web_url): static
    {
        $this->web_url = $web_url;

        return $this;
    }

    public function getWebAdminUrl(): ?string
    {
        return $this->web_admin_url;
    }

    public function setWebAdminUrl(?string $web_admin_url): static
    {
        $this->web_admin_url = $web_admin_url;

        return $this;
    }

    public function getWebAdminLogin(): ?string
    {
        return $this->web_admin_login;
    }

    public function setWebAdminLogin(?string $web_admin_login): static
    {
        $this->web_admin_login = $web_admin_login;

        return $this;
    }

    public function getWebAdminPassword(): ?string
    {
        return $this->web_admin_password;
    }

    public function setWebAdminPassword(?string $web_admin_password): static
    {
        $this->web_admin_password = $web_admin_password;

        return $this;
    }

    public function getHttpAuthLogin(): ?string
    {
        return $this->http_auth_login;
    }

    public function setHttpAuthLogin(?string $http_auth_login): static
    {
        $this->http_auth_login = $http_auth_login;

        return $this;
    }

    public function getHttpAuthPassword(): ?string
    {
        return $this->http_auth_password;
    }

    public function setHttpAuthPassword(?string $http_auth_password): static
    {
        $this->http_auth_password = $http_auth_password;

        return $this;
    }
    public function getPhpVersion(): ?string
    {
        return $this->php_version;
    }

    public function setPhpVersion(string $php_version): static
    {
        $this->php_version = $php_version;

        return $this;
    }

    public function getFrameworkVersion(): ?string
    {
        return $this->framework_version;
    }

    public function setFrameworkVersion(string $framework_version): static
    {
        $this->framework_version = $framework_version;

        return $this;
    }
}
