<?php

namespace App\Entity;

use App\Entity\Utils\Priority;
use App\Entity\Utils\Status;
use App\Repository\TicketRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

use function PHPSTORM_META\type;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    public function __construct()
    {
        $this->status = Status::OPEN;
        $this->priority = Priority::LOW;
        $this->statusHistory = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket.index', 'ticket.show'])]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull()]
    #[Groups(['ticket.index'])]
    private string $title;

    #[ORM\Column(type: 'text', length: 16777215)]
    #[Assert\NotNull()]
    #[Groups(['ticket.show'])]
    private string $description;

    #[ORM\Column(enumType: Status::class, type: 'integer', nullable: false)]
    // #[Assert\Isin(Status::class)]
    #[Groups(['ticket.index'])]
    private Status $status;

    #[ORM\Column(enumType: Priority::class, type: 'integer', nullable: false)]
    // #[Assert\Isin(Priority::class)]
    #[Groups(['ticket.index'])]
    private Priority $priority;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'dead_line')]
    #[Assert\DateTime()]
    #[Groups(['ticket.index'])]
    #[SerializedName('dead_line')]
    private ?DateTime $deadLine = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedTickets')]
    #[ORM\JoinColumn(name: 'assign_user_id')]
    #[Groups(['ticket.show'])]
    #[SerializedName('assigner_to')]
    private ?User $assignedTo = null;

    #[ORM\OneToMany(targetEntity: TicketStatusHistory::class, mappedBy: 'ticket')]
    #[Groups(['ticket.show'])]
    private Collection $statusHistory;

    #[ORM\Column(type: 'datetime', nullable: false, name: 'created_at', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: false, name: 'updated_at', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTime $updatedAt;


    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(Priority $priority): void
    {
        $this->priority = $priority;
    }

    public function getDeadLine(): ?DateTime
    {
        return $this->deadLine;
    }

    public function setDeadLine(DateTime $deadLine): void
    {
        $this->deadLine = $deadLine;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
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
