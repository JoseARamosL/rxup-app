<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher // <--- Inyectar
    ) {}

    public function authenticate(Request $request): Passport
    {
        $identifier = $request->getPayload()->getString('_username');
        $password = $request->getPayload()->getString('_password') ?: 'nopassword';

        return new Passport(
            new UserBadge($identifier, fn() => $this->userRepository->findOneByEmailOrPhone($identifier) ?: throw new UserNotFoundException()),
            new \Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials(
                function($credentials, User $user) {
                    if (in_array('ROLE_ADMIN', $user->getRoles())) {
                        // Si es admin, comprobamos el hash de la contraseña que metió
                        return $this->passwordHasher->isPasswordValid($user, $credentials);
                    }
                    // Si es jugador, ignoramos la contraseña y entra
                    return true;
                },
                $password
            ),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Si el login es exitoso, redirigimos a la home o al listado de torneos
        return new RedirectResponse($this->urlGenerator->generate('app_tournament_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
