<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{

    private PaginatorInterface $paginator;
    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, User::class);
        $this->paginator = $paginator;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findUsersWithPagination(int $page, int $limit)
    {
        $queryBuilder = $this->createQueryBuilder('t');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }

    /**
     * Used to return all users with ROLE_SUPPORT or ROLE_ADMIN
     */
    public function findSupportUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :support_role OR u.roles LIKE :admin_role')
            ->setParameter('support_role', '%"ROLE_SUPPORT"%')
            ->setParameter('admin_role', '%"ROLE_ADMIN"%')
            ->getQuery()
            ->getResult();
    }
}
