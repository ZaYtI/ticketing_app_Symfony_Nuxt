<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class TicketController extends AbstractController
{
    #[Route('api/ticket', methods: 'GET')]
    public function index(TicketRepository $repository): JsonResponse
    {
        $tickets = $repository->findAll();
        return $this->json($tickets, 200, [], [
            'groups' => ['ticket.index']
        ]);
    }

    #[Route('api/ticket/{id}', requirements: ['id' => Requirement::DIGITS], methods: 'GET')]
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->json($ticket, 200, [], [
            'groups' => ['ticket.index', 'ticket.show']
        ]);
    }
}
