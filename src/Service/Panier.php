<?php

namespace App\Service;

use App\Entity\Variante;
use App\Repository\VarianteRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Panier stocké en session sous la forme [idVariante => quantité].
 *
 * Les quantités sont toujours ramenées au stock disponible, et les variantes
 * qui ne sont plus vendables (modèle repassé en production, supprimé…) sont retirées.
 */
class Panier implements ResetInterface
{
    private const CLE_SESSION = 'panier';

    /** @var LignePanier[]|null cache des lignes pour la requête en cours */
    private ?array $lignes = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly VarianteRepository $varianteRepository,
    ) {
    }

    /**
     * Ajoute une paire. Retourne false si le stock ne permet pas d'en ajouter une de plus.
     */
    public function ajouter(Variante $variante, int $quantite = 1): bool
    {
        $contenu = $this->lireSession();
        $id = $variante->getId();
        $nouvelleQuantite = ($contenu[$id] ?? 0) + $quantite;

        if (!$this->estVendable($variante) || $nouvelleQuantite > $variante->getStock()) {
            return false;
        }

        $contenu[$id] = $nouvelleQuantite;
        $this->ecrireSession($contenu);

        return true;
    }

    /**
     * Fixe la quantité d'une ligne (0 = retirer). Retourne false si le stock est insuffisant.
     */
    public function modifierQuantite(Variante $variante, int $quantite): bool
    {
        if ($quantite <= 0) {
            $this->retirer($variante);

            return true;
        }

        if ($quantite > $variante->getStock()) {
            return false;
        }

        $contenu = $this->lireSession();
        if (!isset($contenu[$variante->getId()])) {
            return false;
        }

        $contenu[$variante->getId()] = $quantite;
        $this->ecrireSession($contenu);

        return true;
    }

    public function retirer(Variante $variante): void
    {
        $contenu = $this->lireSession();
        unset($contenu[$variante->getId()]);
        $this->ecrireSession($contenu);
    }

    public function vider(): void
    {
        $this->ecrireSession([]);
    }

    /**
     * @return LignePanier[]
     */
    public function getLignes(): array
    {
        if ($this->lignes !== null) {
            return $this->lignes;
        }

        $contenu = $this->lireSession();
        if ($contenu === []) {
            return $this->lignes = [];
        }

        $variantes = $this->varianteRepository->findAvecModele(array_keys($contenu));

        $lignes = [];
        $contenuNettoye = [];
        foreach ($variantes as $variante) {
            $quantite = min($contenu[$variante->getId()], $variante->getStock());
            if ($quantite > 0 && $this->estVendable($variante)) {
                $lignes[] = new LignePanier($variante, $quantite);
                $contenuNettoye[$variante->getId()] = $quantite;
            }
        }

        // Si le stock a bougé depuis l'ajout, on corrige la session
        if ($contenuNettoye != $contenu) {
            $this->ecrireSession($contenuNettoye);
        }

        return $this->lignes = $lignes;
    }

    /** Nombre total de paires. */
    public function getNombre(): int
    {
        return array_sum(array_map(fn (LignePanier $l) => $l->quantite, $this->getLignes()));
    }

    /** Total en centimes. */
    public function getTotal(): int
    {
        return array_sum(array_map(fn (LignePanier $l) => $l->getSousTotal(), $this->getLignes()));
    }

    public function estVide(): bool
    {
        return $this->getLignes() === [];
    }

    public function reset(): void
    {
        $this->lignes = null;
    }

    private function estVendable(Variante $variante): bool
    {
        return $variante->getModele()?->isDisponible() ?? false;
    }

    /** @return array<int, int> */
    private function lireSession(): array
    {
        $requete = $this->requestStack->getCurrentRequest();

        // Pas de cookie de session : panier vide, sans démarrer de session inutilement
        if ($requete === null || (!$requete->hasPreviousSession() && !$requete->getSession()->isStarted())) {
            return [];
        }

        return $requete->getSession()->get(self::CLE_SESSION, []);
    }

    /** @param array<int, int> $contenu */
    private function ecrireSession(array $contenu): void
    {
        $this->requestStack->getSession()->set(self::CLE_SESSION, $contenu);
        $this->lignes = null;
    }
}
