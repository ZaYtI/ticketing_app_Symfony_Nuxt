<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketStatusHistory;
use App\Entity\User;
use App\Entity\Utils\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketStatusHistory>
 */
class TicketStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketStatusHistory::class);
    }
}
