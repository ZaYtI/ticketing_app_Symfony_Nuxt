<?php

namespace App\MessageHandler;

use App\Message\UnassignedTicketMessage;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UnassignedTicketMessageHandler
{

    public function __construct(
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }
    public function __invoke(UnassignedTicketMessage $message): void
    {
        $unassignedTickets = $this->ticketRepository->findUnassignedTickets();
        if (count($unassignedTickets) === 0) {
            $this->logger->info('Aucun ticket non assigné trouvé.');
        }

        $availableAgents = $this->userRepository->findSupportUsers();

        if (count($availableAgents) === 0) {
            $this->logger->info('Aucun agent disponible pour l\'assignation.');
        }

        foreach ($unassignedTickets as $ticket) {
            $agentToAssign = $this->findLeastBusyAgent($availableAgents);
            
            if ($agentToAssign) {
                $ticket->setAssignedTo($agentToAssign);
                $ticket->setUpdatedAt(new \DateTime());
                
                $this->logger->info(sprintf(
                    'Ticket #%d assigné à l\'agent %s',
                    $ticket->getId(),
                    $agentToAssign->getEmail()
                ));
            }
        }

        $this->entityManager->flush();
    }

    private function findLeastBusyAgent(array $agents)
    {
        // Implémentez une logique pour trouver l'agent avec le moins de tickets
        // Exemple simplifié : premier agent de la liste
        return $agents[0] ?? null;
    }
}
