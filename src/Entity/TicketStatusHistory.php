<?php

namespace App\Entity;

use App\Entity\Utils\Status;
use App\Repository\TicketStatusHistoryRepository;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use function Symfony\Component\Clock\now;

#[ORM\Entity(repositoryClass: TicketStatusHistoryRepository::class)]
#[ORM\Table(name: "ticket_status_history")]
class TicketStatusHistory
{

    public function __construct()
    {
        $this->changeAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: 'integer', nullable: false, enumType: Status::class)]
    #[Groups(['ticket.show'])]
    private Status $status;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(name: 'ticket_id')]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(name: "changed_user_id")]
    #[Groups(['ticket.show'])]
    private User $changedBy;


    #[ORM\Column(name: 'change_at', nullable: false)]
    #[Assert\DateTime()]
    private \DateTime $changeAt;

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }



    public function getChangeAt(): \DateTime
    {
        return $this->changeAt;
    }

    public function setChangeAt(DateTime $changeAt): void
    {
        $this->changeAt = $changeAt;
    }

    public function getChangedBy(): User
    {
        return $this->changedBy;
    }

    public function setChangedBy(User $user)
    {
        $this->changedBy = $user;
    }
}
