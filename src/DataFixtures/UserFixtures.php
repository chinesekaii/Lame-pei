<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes de test (environnement de développement uniquement).
 *
 *  - admin@lamepei.re  / admin1234   (ROLE_ADMIN)
 *  - client@lamepei.re / client1234  (ROLE_USER)
 */
class UserFixtures extends Fixture
{
    public const ADMIN = 'user-admin';
    public const CLIENT = 'user-client';

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@lamepei.re')
            ->setPrenom('Admin')
            ->setNom("L'Âme Peï")
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);
        $this->addReference(self::ADMIN, $admin);

        $client = (new User())
            ->setEmail('client@lamepei.re')
            ->setPrenom('Maëva')
            ->setNom('Payet');
        $client->setPassword($this->hasher->hashPassword($client, 'client1234'));
        $manager->persist($client);
        $this->addReference(self::CLIENT, $client);

        $manager->flush();
    }
}
