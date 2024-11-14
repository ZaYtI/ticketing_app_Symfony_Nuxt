<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user')]
    #[IsGranted('ROLE_ADMIN', message: 'No access! Get out!')]
    public function index(
        UserRepository $userRepo,
        Request $request
    ): JsonResponse
    {
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
        ],200,[],['groups' => ['user.index']]);
    }
}
