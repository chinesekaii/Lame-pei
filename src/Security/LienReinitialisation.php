<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Lien « mot de passe oublié », sans table en base :
 *  - l'URL est signée par Symfony (impossible de la modifier) et expire après DUREE_MINUTES ;
 *  - elle contient une empreinte du mot de passe actuel : dès que le mot de passe change,
 *    le lien ne fonctionne plus (usage unique).
 */
class LienReinitialisation
{
    public const DUREE_MINUTES = 60;

    public function __construct(
        private readonly UriSigner $uriSigner,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generer(User $user): string
    {
        $url = $this->urlGenerator->generate('app_reinitialiser_mot_de_passe', [
            'id' => $user->getId(),
            'empreinte' => $this->empreinte($user),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->uriSigner->sign($url, new \DateInterval('PT'.self::DUREE_MINUTES.'M'));
    }

    /** Vrai si le lien n'a pas été modifié, n'a pas expiré et correspond au mot de passe actuel. */
    public function estValide(Request $request, User $user): bool
    {
        return $this->uriSigner->checkRequest($request)
            && hash_equals($this->empreinte($user), (string) $request->query->get('empreinte'));
    }

    private function empreinte(User $user): string
    {
        return substr(hash('sha256', $user->getPassword().'|'.$user->getEmail()), 0, 24);
    }
}
