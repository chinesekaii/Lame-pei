<?php

namespace App\Controller;

use App\Entity\Variante;
use App\Repository\VarianteRepository;
use App\Service\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class PanierController extends AbstractController
{
    public function __construct(
        private readonly Panier $panier,
        private readonly VarianteRepository $varianteRepository,
    ) {
    }

    #[Route('', name: 'app_panier_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('panier/index.html.twig');
    }

    #[Route('/ajouter', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(Request $request): RedirectResponse
    {
        if (!$this->csrfValide($request)) {
            return $this->retour($request);
        }

        $variante = $this->trouverVariante($request->getPayload()->getInt('variante'));
        if ($variante === null) {
            $this->addFlash('error', 'Merci de choisir une taille.');

            return $this->retour($request);
        }

        if ($this->panier->ajouter($variante)) {
            $this->addFlash('ouvrir_panier', '1');
        } else {
            $this->addFlash('error', sprintf(
                'Désolé, il ne reste plus de paire %s en taille %s.',
                $variante->getModele()->getNom(),
                $variante->getTaille(),
            ));
        }

        return $this->retour($request);
    }

    #[Route('/modifier/{id}', name: 'app_panier_modifier', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function modifier(Variante $variante, Request $request): RedirectResponse
    {
        if (!$this->csrfValide($request)) {
            return $this->retour($request);
        }

        $quantite = $request->getPayload()->getInt('quantite');
        if (!$this->panier->modifierQuantite($variante, $quantite)) {
            $this->addFlash('error', sprintf(
                'Il ne reste que %d paire(s) %s en taille %s.',
                $variante->getStock(),
                $variante->getModele()->getNom(),
                $variante->getTaille(),
            ));
        }

        return $this->retour($request);
    }

    #[Route('/retirer/{id}', name: 'app_panier_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retirer(Variante $variante, Request $request): RedirectResponse
    {
        if (!$this->csrfValide($request)) {
            return $this->retour($request);
        }
        $this->panier->retirer($variante);

        return $this->retour($request);
    }

    private function trouverVariante(int $id): ?Variante
    {
        return $id > 0 ? $this->varianteRepository->find($id) : null;
    }

    private function csrfValide(Request $request): bool
    {
        if ($this->isCsrfTokenValid('panier', $request->getPayload()->getString('_token'))) {
            return true;
        }

        $this->addFlash('error', 'Votre session a expiré, merci de réessayer.');

        return false;
    }

    /**
     * Revient sur la page d'origine (même site uniquement), sinon sur le panier.
     */
    private function retour(Request $request): RedirectResponse
    {
        $referer = (string) $request->headers->get('referer');

        if (str_starts_with($referer, $request->getSchemeAndHttpHost().'/')) {
            return $this->redirect($referer, Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_panier_index', status: Response::HTTP_SEE_OTHER);
    }
}
