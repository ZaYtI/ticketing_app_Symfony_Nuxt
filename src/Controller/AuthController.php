<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/api/register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'message' => 'User registered successfully.'
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to save user: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('api/me', name: 'app_user_me', methods: 'GET')]
    public function userInfo(): JsonResponse
    {
        return $this->json($this->getUser(), 200, [], [
            'groups' => ['user.index']
        ]);
    }

    #[Route('api/logout', name: 'app_user_logout', methods: 'GET')]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Logout successful'], 200);
        
        $response->headers->clearCookie('PHPSESSID');

        $response->headers->clearCookie('jwt-token-ticket');

        return $response;
    }
}
