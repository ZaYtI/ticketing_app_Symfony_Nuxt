<?php

namespace App\Entity;

use App\Entity\Utils\BaseEntity;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    public function __construct()
    {
        $this->assignedTickets = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket.show', 'user.index', 'user.show', 'user.show'])]
    private int $id;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    #[Assert\Email(
        mode: Email::VALIDATION_MODE_STRICT
    )]
    #[Groups(['ticket.show', 'user.index', 'user.show'])]
    private string $email;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user.index', 'user.show'])]
    private array $roles;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'assignedTo')]
    #[Groups(['user.show'])]
    private Collection $assignedTickets;

    #[ORM\OneToMany(targetEntity: TicketStatusHistory::class, mappedBy: 'changedBy')]
    private Collection $actions;

    #[ORM\Column(type: 'datetime', nullable: false, name: 'created_at', options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups(['user.index', 'user.show'])]
    private DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: false, name: 'updated_at', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAssignedTickets(): Collection
    {
        return $this->assignedTickets;
    }

    public function getAction(): Collection
    {
        return $this->actions;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
