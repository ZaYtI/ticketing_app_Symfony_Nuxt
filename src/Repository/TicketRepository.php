<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Ticket::class);
        $this->paginator = $paginator;
    }

    public function findTicketsWithPaginationAndFilters(array $filters = [], int $page = 1, int $limit = 10)
    {

        $queryBuilder = $this->createQueryBuilder('t');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }
}
