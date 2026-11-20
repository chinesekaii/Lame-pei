<?php

namespace App\Controller;

use App\Service\PaiementStripe;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Point d'entrée des notifications Stripe (paiement réussi, session expirée…).
 * À déclarer dans le tableau de bord Stripe : https://<domaine>/stripe/webhook
 * Chaque message est authentifié par sa signature (STRIPE_WEBHOOK_SECRET).
 */
final class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request, PaiementStripe $paiement, LoggerInterface $logger): Response
    {
        try {
            $paiement->traiterWebhook($request->getContent(), (string) $request->headers->get('Stripe-Signature'));
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            $logger->warning('Webhook Stripe refusé : {erreur}', ['erreur' => $e->getMessage()]);

            return new Response('Signature invalide', Response::HTTP_BAD_REQUEST);
        }

        return new Response('ok');
    }
}
