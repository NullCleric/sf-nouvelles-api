<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $pseudo = $data['pseudo'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (!$email || !$pseudo || !$plainPassword) {
            return $this->json([
                'message' => 'Missing fields: email, pseudo and password are required.'
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Invalid email format.'], 400);
        }

        if (mb_strlen($pseudo) < 3 || mb_strlen($pseudo) > 50) {
            return $this->json(['message' => 'Pseudo must be between 3 and 50 characters.'], 400);
        }

        if (mb_strlen($plainPassword) < 8) {
            return $this->json(['message' => 'Password must be at least 8 characters long.'], 400);
        }

        // Check email uniqueness
        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'Email already in use.'], 409);
        }

        // Check pseudo uniqueness
        if ($em->getRepository(User::class)->findOneBy(['pseudo' => $pseudo])) {
            return $this->json(['message' => 'Pseudo already in use.'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $plainPassword)
        );

        $em->persist($user);
        $em->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
        ], 201);
    }
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['message' => 'Missing credentials'], 400);
        }

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Invalid credentials'], 401);
        }

        // Création du token de session
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        // Stockage du token dans la session
        $tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        return $this->json([
            'message' => 'Logged in successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
            ],
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return new Response(null, 204);
    }
}
