<?php

namespace App\Entity;

use App\Entity\Utils\BaseEntity;
use App\Entity\Utils\Priority;
use App\Entity\Utils\Status;
use App\Repository\TicketRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket extends BaseEntity
{
    public function __construct()
    {
        parent::__construct();
        $this->status = Status::OPEN;
        $this->priority = Priority::LOW;
    }

    #[ORM\Column(length: 255)]
    #[Assert\NotNull()]
    private string $title;

    #[ORM\Column(type: 'text', length: 16777215)]
    #[Assert\NotNull()]
    private string $description;

    #[ORM\Column(enumType: Status::class)]
    #[Assert\Isin(Status::class)]
    private Status $status;

    #[ORM\Column(enumType: Priority::class)]
    #[Assert\Isin(Priority::class)]
    private Priority $priority;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'dead_line')]
    #[Assert\DateTime()]
    private ?DateTime $deadLine = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedTickets')]
    #[ORM\JoinColumn(name: 'assign_user_id')]
    private ?User $assignedTo = null;

    #[ORM\OneToMany(targetEntity: TicketStatusHistory::class, mappedBy: 'ticket')]
    private Collection $statusHistory;

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
}
