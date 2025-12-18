<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();

        // $user est ton App\Entity\User
        return new JsonResponse([
            'message' => 'Logged in successfully',
            'user' => [
                'id' => method_exists($user, 'getId') ? $user->getId() : null,
                'email' => method_exists($user, 'getEmail') ? $user->getEmail() : $token->getUserIdentifier(),
                'pseudo' => method_exists($user, 'getPseudo') ? $user->getPseudo() : null,
            ],
        ]);
    }
}
