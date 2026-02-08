<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/check-user/{identifier}', name: 'app_check_user', methods: ['GET'])]
    public function checkUser(string $identifier, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneByEmailOrPhone($identifier);

        // Si no existe el usuario, devolvemos false para que no pida pass
        if (!$user) {
            return new JsonResponse(['isAdmin' => false, 'exists' => false]);
        }

        return new JsonResponse([
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles()),
            'exists' => true
        ]);
    }
}
