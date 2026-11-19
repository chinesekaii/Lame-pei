<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Utilisateur connecté mais sans les droits suffisants (ex. un client sur /admin) :
 * au lieu d'une page d'erreur 403, on le renvoie vers la connexion avec une explication.
 */
class AccesRefuseHandler implements AccessDeniedHandlerInterface
{
    use TargetPathTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $session = $request->getSession();

        // Après connexion avec le bon compte, on revient directement sur la page demandée
        if ($request->isMethod('GET')) {
            $this->saveTargetPath($session, 'main', $request->getUri());
        }

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', str_starts_with($request->getPathInfo(), '/admin')
                ? 'Cette partie du site est réservée aux administrateurs. Connectez-vous avec un compte administrateur.'
                : 'Vous n\'avez pas accès à cette page.');
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
