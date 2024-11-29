<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{

    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Ticket::class);
        $this->paginator = $paginator;
    }

    public function findTicketsWithPaginationAndFilters(array $filters, int $page, int $limit)
    {
        $queryBuilder = $this->createQueryBuilder('t');

        foreach ($filters as $field => $value) {
            if ($value !== null) {
                switch ($field) {
                    case 'assign_user_id':
                        $queryBuilder->andWhere('t.assignedTo = :assign_user_id')
                            ->setParameter('assign_user_id', $value);
                        break;
                    case 'status':
                        $queryBuilder->andWhere('t.status = :status')
                            ->setParameter('status', $value);
                        break;
                    default:
                        break;
                }
            }
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }
}
