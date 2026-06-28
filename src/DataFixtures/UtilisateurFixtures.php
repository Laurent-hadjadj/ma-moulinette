<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création UtilisateurFixtures.
 * Contrat : 5 utilisateurs (admin actif + 4 personnes inactives). Aurélie porte les
 * clés de préférence statut/suivi_projet/favori_projet/favori_version exigées par
 * UtilisateurRepositoryTest.
 */
class UtilisateurFixtures extends Fixture
{
    /** Hash bcrypt cost 13 partagé pour tous les utilisateurs de test (mot de passe « test »). */
    private const PASSWORD_HASH = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $admin = (new Utilisateur())
            ->setPrenom('admin')
            ->setNom('@ma-moulinette')
            ->setAvatar('chiffre/00.png')
            ->setCourriel('admin@ma-moulinette.fr')
            ->setPassword(self::PASSWORD_HASH)
            ->setRoles(['ROLE_ADMIN'])
            ->setGroupeId('01HZZZZZZZZZZZZZZZZZZZZZZZ')
            ->setListeGroupeFonctionnel([])
            ->setActif(true)
            ->setPreference($this->defaultPreference())
            ->setResetPassword(false)
            ->setResetPasswordCount(0)
            ->setDateEnregistrement($now);
        $manager->persist($admin);

        $aurelie = (new Utilisateur())
            ->setPrenom('Aurélie')
            ->setNom('Petit-Coeur')
            ->setAvatar('chiffre/01.png')
            ->setCourriel('aurelie.petit-coeur@ma-moulinette.fr')
            ->setPassword(self::PASSWORD_HASH)
            ->setRoles(['ROLE_GESTIONNAIRE'])
            ->setGroupeId('01HZZZZZZZZZZZZZZZZZZZZZA1')
            ->setListeGroupeFonctionnel([])
            ->setActif(false)
            ->setPreference($this->defaultPreference())
            ->setResetPassword(true)
            ->setResetPasswordCount(1)
            ->setDateEnregistrement($now);
        $manager->persist($aurelie);

        $emma = (new Utilisateur())
            ->setPrenom('Emma')
            ->setNom('Durand')
            ->setAvatar('chiffre/02.png')
            ->setCourriel('emma.durand@ma-moulinette.fr')
            ->setPassword(self::PASSWORD_HASH)
            ->setRoles(['ROLE_UTILISATEUR'])
            ->setGroupeId('01HZZZZZZZZZZZZZZZZZZZZZE2')
            ->setListeGroupeFonctionnel([])
            ->setActif(false)
            ->setPreference($this->defaultPreference())
            ->setDateEnregistrement($now);
        $manager->persist($emma);

        $josh = (new Utilisateur())
            ->setPrenom('Josh')
            ->setNom('Martin')
            ->setAvatar('chiffre/03.png')
            ->setCourriel('josh.martin@ma-moulinette.fr')
            ->setPassword(self::PASSWORD_HASH)
            ->setRoles(['ROLE_UTILISATEUR'])
            ->setGroupeId('01HZZZZZZZZZZZZZZZZZZZZZJ3')
            ->setListeGroupeFonctionnel([])
            ->setActif(false)
            ->setPreference($this->defaultPreference())
            ->setDateEnregistrement($now);
        $manager->persist($josh);

        $nathan = (new Utilisateur())
            ->setPrenom('Nathan')
            ->setNom('Lefevre')
            ->setAvatar('chiffre/04.png')
            ->setCourriel('nathan.lefevre@ma-moulinette.fr')
            ->setPassword(self::PASSWORD_HASH)
            ->setRoles(['ROLE_COLLECTE'])
            ->setGroupeId('01HZZZZZZZZZZZZZZZZZZZZZN4')
            ->setListeGroupeFonctionnel([])
            ->setActif(false)
            ->setPreference($this->defaultPreference())
            ->setDateEnregistrement($now);
        $manager->persist($nathan);

        $manager->flush();
    }

    /**
     * Structure de préférence vide mais avec toutes les clés attendues par les tests.
     *
     * @return array<string, mixed>
     */
    private function defaultPreference(): array
    {
        return [
            'statut'         => [],
            'suivi_projet'   => [],
            'favori_projet'  => [],
            'favori_version' => [],
        ];
    }
}
