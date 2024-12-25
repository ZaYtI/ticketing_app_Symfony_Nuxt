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

    public function findTicketsWithPaginationAndFilters(
        array $filters,
        int $page, 
        int $limit,
        ?string $sort = null,
        ?string $direction = 'asc'
    ) {
        $queryBuilder = $this->createQueryBuilder('t');
        
        $allowedFilters = [
            'assign_user_id' => 't.assignedTo',
            'status' => 't.status', 
            'created_by_id' => 't.createdBy',
            'priority' => 't.priority',
            'id' => 't.id' // Modifié 'ids' en 'id'
        ];
    
        foreach ($filters as $field => $value) {
            if (isset($allowedFilters[$field]) && $value !== null) {
                // Cas spécial pour le filtre d'ID qui accepte un tableau
                if ($field === 'id' && is_array($value) && !empty($value)) {
                    $queryBuilder->andWhere($queryBuilder->expr()->in($allowedFilters[$field], ':' . $field))
                        ->setParameter($field, $value);
                } else {
                    $queryBuilder->andWhere(sprintf('%s = :%s', $allowedFilters[$field], $field))
                        ->setParameter($field, $value);
                }
            }
        }
    
        if (isset($filters['or']) && is_array($filters['or'])) {
            $orX = $queryBuilder->expr()->orX();
            foreach ($filters['or'] as $field => $value) {
                if (isset($allowedFilters[$field]) && $value !== null) {
                    $orX->add(sprintf('%s = :or_%s', $allowedFilters[$field], $field));
                    $queryBuilder->setParameter("or_$field", $value);
                }
            }
            if ($orX->count() > 0) {
                $queryBuilder->andWhere($orX);
            }
        }
    
        $allowedSortFields = ['id', 'status', 'priority', 'created_at', 'updated_at'];
        if ($sort && in_array($sort, $allowedSortFields)) {
            $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            $queryBuilder->orderBy("t.$sort", $direction);
        } else {
            $queryBuilder->orderBy('t.id', 'ASC');
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

        if (isset($filters['createdBy'])) {
            $qb->andWhere('t.createdBy = :createdBy')
                ->setParameter('createdBy', $filters['createdBy']);
        }
        if (isset($filters['assignedTo'])) {
            $qb->orWhere('t.assignedTo = :assignedTo')
                ->setParameter('assignedTo', $filters['assignedTo']);
        }
        
        return $qb->getQuery()->getResult();
    }

    public function getTickets12LastMonths($filters = [])
    {
        $oneYearAgo = new \DateTime();
        $oneYearAgo->modify('-11 months');
        $oneYearAgoStr = $oneYearAgo->format('Y-m-d H:i:s');

        $sql = "SELECT 
                CAST(strftime('%m', created_at) AS INTEGER) as m, 
                COUNT(id) as count 
            FROM ticket 
            WHERE created_at > :oneYearAgo";

        $params = ['oneYearAgo' => $oneYearAgoStr];

        // Ajout du filtre assignedTo si présent
        if (isset($filters['createdBy'])) {
            $sql .= " AND created_by_id = :createdBy";
            $params['createdBy'] = $filters['createdBy'];
        }
        //Pour les tickets assignés aux supports
        if (isset($filters['assignedTo'])) {
            $sql .= " OR assign_user_id = :assignedTo";
            $params['assignedTo'] = $filters['assignedTo'];
        }

        $sql .= " GROUP BY strftime('%Y', created_at), strftime('%m', created_at)";

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);

        return $stmt->executeQuery($params)->fetchAllAssociative();
    }
}
