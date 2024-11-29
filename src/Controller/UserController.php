<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('api/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN', message: 'You must be an admin for this action')]
    public function index(
        UserRepository $userRepo,
        Request $request
    ): JsonResponse {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $paginatedUsers = $userRepo->findUsersWithPagination($page, $limit);

        $totalPages = (int) ceil($paginatedUsers->getTotalItemCount() / $limit);
        $nextPageUrl = $page < $totalPages
            ? 'http://localhost:8000/api/ticket?page=' . ($page + 1) . '&limit=' . $limit
            : null;

        return $this->json([
            'items' => $paginatedUsers->getItems(),
            'meta' => [
                'total_items' => $paginatedUsers->getTotalItemCount(),
                'current_page' => $paginatedUsers->getCurrentPageNumber(),
                'total_pages' => $totalPages,
                'next_pages' => $nextPageUrl
            ]
        ], 200, [], ['groups' => ['user.index']]);
    }

    #[Route('/{id}', name: 'app_user_show', requirements: ['id' => Requirement::DIGITS], methods: 'GET')]
    #[IsGranted('ROLE_ADMIN', message: 'You must be an admin for this action')]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, 200, [], [
            'groups' => ['user.index', 'user.show', 'ticket.index']
        ]);
    }
}
