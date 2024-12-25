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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TicketController extends AbstractController
{
    #[Route('api/ticket', name: 'findAllTicket', methods: 'GET')]
    public function index(
        TicketRepository $repository,
        Request $request
    ): JsonResponse {

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        /** @var UserInterface|User $currentUser */
        $currentUser = $this->getUser();

        $filters = [];
        if (!$currentUser->isAdmin()) {
            $filters['or'] = [
                'assign_user_id' => $currentUser->getId(),
                'created_by_id' => $currentUser->getId(),
            ];
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

    #[Route('api/ticket/assignable', name: 'app_ticket_assignable', methods: 'GET')]
    #[IsGranted('ROLE_SUPPORT', message: 'You must be an support for this action')]
    public function getAssignableUser(UserRepository $repository): JsonResponse
    {
        return $this->json($repository->findSupportUsers(), 200, [], [
            'groups' => ['user.select']
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
    public function createTicket(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepo
    ): JsonResponse {
        /** @var UserInterface|User $currentUser */
        $currentUser = $this->getUser();
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

        $ticket->setCreatedBy($this->getUser());
        $ticket->setAssignedTo($userRepo->find($assignedToUserId));

        $entityManager->persist($ticket);
        $entityManager->flush();

        $location = $urlGenerator->generate('detailTicket', ['id' => $ticket->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($ticket, JsonResponse::HTTP_CREATED, ["Location" => $location], [
            'groups' => ['ticket.index', 'ticket.show']
        ]);
    }

    #[Route('api/ticket/{id}', name: 'updateTicket', requirements: ['id' => Requirement::DIGITS], methods: 'PUT')]
    public function updateTicket(
        Request $request,
        Ticket $ticket,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserRepository $userRepo
    ): JsonResponse {
        $updatedTicket = $serializer->deserialize($request->getContent(), Ticket::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $ticket]);

        $errors = $validator->validate($updatedTicket);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$ticket) {
            return $this->json(['error' => 'Ticket not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var UserInterface|User $currentUser */
        $currentUser = $this->getUser();
        /** @var UserInterface|User $currentUser */
        $assignedUser = $ticket->getAssignedTo();
        if (
            !$currentUser->isAdmin() &&
            (!$assignedUser || $assignedUser->getId() !== $currentUser->getId()) &&
            ($ticket->getCreatedBy()->getId() !== $currentUser->getId())
        ) {
            return $this->json(['error' => 'You are not authorized to update this ticket'], JsonResponse::HTTP_FORBIDDEN);
        }

        $content = $request->toArray();
        if (array_key_exists('assigned_to_user_id', $content)) {
            $assignedToUserId = $content['assigned_to_user_id'] ?? -1;
            $ticket->setAssignedTo($assignedToUserId === null ? null : $userRepo->find($assignedToUserId));
        }
        $entityManager->persist($ticket);
        $entityManager->flush();

        return $this->json($ticket, JsonResponse::HTTP_OK, [], [
            'groups' => ['ticket.index', 'ticket.show']
        ]);
    }

    #[Route('api/ticket/chart', name: 'ticket_chart', methods: 'GET')]
    public function getTicketChart(
        TicketRepository $ticketRepository
    ): JsonResponse {
        /** @var UserInterface|User $currentUser */
        $currentUser = $this->getUser();

        $filters = [];
        if (!$currentUser->isAdmin()) {
            $filters['assigned_user_id'] = $currentUser->getId();
        }

        $ticketsByStatus = $ticketRepository->getTicketsByStatus($filters);

        //Tickets sur les 12 derniers mois
        $ticketsLast12Months = $ticketRepository->getTickets12LastMonths($filters);

        return $this->json([
            'tickets_12_last_months' => $ticketsLast12Months,
            'tickets_by_status' => $ticketsByStatus,
        ], JsonResponse::HTTP_OK);
    }
}
