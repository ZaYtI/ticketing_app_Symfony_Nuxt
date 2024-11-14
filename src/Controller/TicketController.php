<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TicketController extends AbstractController
{
    #[Route('api/ticket', name: 'findAllTicket', methods: 'GET')]
    public function index(TicketRepository $repository, Request $request): JsonResponse
    {

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        /** @var UserInterface|User $currentUser */
        $currentUser = $this->getUser();

        $filters = [];
        if (!$currentUser->isAdmin()) {
            $filters['assign_user_id'] = $currentUser->getId();
        }

        $paginatedTickets = $repository->findTicketsWithPaginationAndFilters($filters, $page, $limit);

        $totalPages = (int) ceil($paginatedTickets->getTotalItemCount() / $limit);

        $nextPageUrl = $page < $totalPages
            ? 'http://localhost:8000/api/ticket?page=' . ($page + 1) . '&limit=' . $limit
            : null;

        return $this->json([
            'items' => $paginatedTickets->getItems(),
            'meta' => [
                'total_items' => $paginatedTickets->getTotalItemCount(),
                'current_page' => $paginatedTickets->getCurrentPageNumber(),
                'total_pages' => $totalPages,
                'next_pages' => $nextPageUrl
            ]
        ], 200, [], [
            'groups' => ['ticket.index']
        ]);
    }

    #[Route('api/ticket/{id}', name: 'detailTicket', requirements: ['id' => Requirement::DIGITS], methods: 'GET')]
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->json($ticket, 200, [], [
            'groups' => ['ticket.index', 'ticket.show']
        ]);
    }

    #[Route('api/ticket', methods: 'POST')]
    public function createTicket(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator, UrlGeneratorInterface $urlGenerator, UserRepository $userRepo): JsonResponse
    {

        $ticket = $serializer->deserialize($request->getContent(), Ticket::class, 'json');

        $errors = $validator->validate($ticket);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $content = $request->toArray();
        $assignedToUserId = $content['assigned_to_user_id'] ?? -1;

        $ticket->setAssignedTo($userRepo->find($assignedToUserId));

        $entityManager->persist($ticket);
        $entityManager->flush();

        $location = $urlGenerator->generate('detailTicket', ['id' => $ticket->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($ticket, JsonResponse::HTTP_CREATED, ["Location" => $location], [
            'groups' => ['ticket.index', 'ticket.show']
        ]);
    }
}
