<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user', methods: ['GET'])]
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

    #[Route('/api/user', name: 'create_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied!')]
    public function createUser(
        Request $request,
        UserRepository $userRepo,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // Validation : vérifier les contraintes de l'entité User
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages]);
        }

        if ($userRepo->findOneBy(['email' => $user->getEmail()])) {
            return $this->json(['error' => 'Email déjà utilisé']);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        // Sauvegarde dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur bien créé'], 201);
    }

    #[Route('api/user/{id}', name: 'update_user', requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied!')]
    public function updateUser(
        Request                $request,
        User                   $user,
        SerializerInterface    $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface     $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $user
        ]);

        $errors = $validator->validate($updatedUser);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }


        $content = $request->toArray();
        if (!empty($content['password'])) {
            // Hachage du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($updatedUser, $content['password']);
            $updatedUser->setPassword($hashedPassword);
        }


        $entityManager->persist($updatedUser);
        $entityManager->flush();

        return $this->json(['message' => 'L\'utilisateur a bien été mis à jour.'], JsonResponse::HTTP_OK, [], [
            'groups' => ['user.index', 'user.show']
        ]);
    }

}
