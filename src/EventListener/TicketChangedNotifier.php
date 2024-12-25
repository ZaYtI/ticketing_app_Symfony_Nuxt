<?php

namespace App\EventListener;

use App\Entity\Ticket;
use App\Entity\TicketStatusHistory;
use App\Entity\Utils\Status;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Ticket::class)]
class TicketChangedNotifier
{
    private $entityManager;
    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function postUpdate(Ticket $ticket): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        
        $changeSet = $unitOfWork->getEntityChangeSet($ticket);

        if (isset($changeSet['status'])) {
            $oldStatus = $changeSet['status'][0];
            $newStatus = $changeSet['status'][1];

            if($oldStatus != $newStatus){
                $statusHistory = new TicketStatusHistory();
                $statusHistory->setTicket($ticket);
                $statusHistory->setStatus(Status::from($newStatus));
                
                $user = $this->tokenStorage->getToken()->getUser();
                $statusHistory->setChangedBy($user);
                
                $this->entityManager->persist($statusHistory);
                $this->entityManager->flush();
            }
        }
    }
}


