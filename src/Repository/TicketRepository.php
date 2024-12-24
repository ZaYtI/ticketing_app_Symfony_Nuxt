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

    /**
     * Trouve les tickets qui n'ont pas était mis a jour depuis plus de 2 semaines
     *
     * @return Ticket[]
     */
    public function findUnassignedTickets()
    {
        $twoWeeksAgo = new \DateTime();
        $twoWeeksAgo->modify('-14 days');

        return $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo IS NULL')
            ->andWhere('t.updatedAt < :twoWeeksAgo')
            ->setParameter('twoWeeksAgo', $twoWeeksAgo)
            ->getQuery()
            ->getResult();
    }

    public function getTicketsByStatus($filters = [])
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->orderBy('t.status', 'ASC');

        if (isset($filters['assigned_to'])) {
            $qb->andWhere('t.assignedTo = :assignedTo')
                ->setParameter('assignedTo', $filters['assigned_to']);
        }
        
        return $qb->getQuery()->getResult();
    }

    public function getTickets12LastMonths($filters = [])
    {
        $oneYearAgo = new \DateTime();
        $oneYearAgo->modify('-12 months');
        $oneYearAgoStr = $oneYearAgo->format('Y-m-d H:i:s');

        $sql = "SELECT 
                CAST(strftime('%m', created_at) AS INTEGER) as m, 
                COUNT(id) as count 
            FROM ticket 
            WHERE created_at > :oneYearAgo";

        $params = ['oneYearAgo' => $oneYearAgoStr];

        // Ajout du filtre assignedTo si présent
        if (isset($filters['assigned_to'])) {
            $sql .= " AND assigned_to = :assignedTo";
            $params['assignedTo'] = $filters['assigned_to'];
        }

        $sql .= " GROUP BY strftime('%Y', created_at), strftime('%m', created_at)";

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);

        return $stmt->executeQuery($params)->fetchAllAssociative();
    }
}
