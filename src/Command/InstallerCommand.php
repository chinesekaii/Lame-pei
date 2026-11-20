<?php

namespace App\Command;

use App\Catalogue\CatalogueSynchroniseur;
use App\Entity\Modele;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Prépare une base neuve en production (lancée à chaque démarrage du conteneur, sans risque) :
 *  - si la base ne contient encore aucun modèle : catalogue de référence, galerie photos,
 *    retours clients (en attente) ;
 *  - compte administrateur à partir de ADMIN_EMAIL et ADMIN_PASSWORD s'il n'existe pas encore
 *    (un compte existant n'est jamais modifié : son mot de passe se change dans l'espace client).
 *
 *   php bin/console app:installer
 */
#[AsCommand(name: 'app:installer', description: 'Initialise une base neuve (catalogue, galerie, compte admin)')]
final class InstallerCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CatalogueSynchroniseur $synchroniseur,
        private readonly ImporterAvisCommand $importeurAvis,
        private readonly UserPasswordHasherInterface $hasher,
        #[Autowire(env: 'default::ADMIN_EMAIL')] private readonly ?string $adminEmail,
        #[Autowire(env: 'default::ADMIN_PASSWORD')] private readonly ?string $adminMotDePasse,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        // 1. Catalogue et contenus, uniquement sur une base vide
        if ($this->em->getRepository(Modele::class)->count([]) === 0) {
            $io->listing($this->synchroniseur->synchroniser(imposerStock: true));

            ($this->importeurAvis)($io);
            $io->success('Base initialisée : catalogue, galerie photos et retours clients (en attente).');
        } else {
            $io->note('La base contient déjà le catalogue : rien à initialiser.');
        }

        // 2. Compte administrateur
        if (!$this->adminEmail || !$this->adminMotDePasse) {
            $io->note('ADMIN_EMAIL / ADMIN_PASSWORD non définis : aucun compte admin créé.');

            return Command::SUCCESS;
        }

        $email = mb_strtolower(trim($this->adminEmail));
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email]) !== null) {
            $io->note(sprintf('Le compte %s existe déjà : il n\'est pas modifié.', $email));

            return Command::SUCCESS;
        }

        if (mb_strlen($this->adminMotDePasse) < 12) {
            $io->error('ADMIN_PASSWORD doit faire au moins 12 caractères : compte admin non créé.');

            return Command::FAILURE;
        }

        $admin = (new User())
            ->setEmail($email)
            ->setPrenom('Admin')
            ->setNom('L\'ÂME PEÏ')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, $this->adminMotDePasse));
        $this->em->persist($admin);
        $this->em->flush();

        $io->success(sprintf('Compte administrateur créé : %s', $email));

        return Command::SUCCESS;
    }
}
