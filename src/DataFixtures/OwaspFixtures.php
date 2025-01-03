<?php

namespace App\DataFixtures;

use App\Entity\Owasp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description OwaspFixtures]
 */
class OwaspFixtures extends Fixture
{
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $referentialOwasp = 2017;
    private string $version = '1.2.0-RELEASE';
    private static $dateVersion = '2024-07-10 15:26:07+02';
    private static $effortTotal = 0;
    private static $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';

    public function load(ObjectManager $manager): void
    {
        $modeCollecte = ['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

        foreach ($modeCollecte as $mode) {
            $owasp = new Owasp();
            $owasp->setMavenKey(static::$mavenKey);
            $owasp->setReferentialOwasp(static::$referentialOwasp);
            $owasp->setVersion($this->version);
            $owasp->setDateVersion(new \DateTimeImmutable(static::$dateVersion));
            $owasp->setEffortTotal(static::$effortTotal);

            for ($i = 0; $i < 10; $i++) {
                $owasp->{"setA" . ($i + 1)}(static::$a[$i]);
                $owasp->{"setA" . ($i + 1) . "Blocker"}(static::$aBlocker[$i]);
                $owasp->{"setA" . ($i + 1) . "Critical"}(static::$aCritical[$i]);
                $owasp->{"setA" . ($i + 1) . "Major"}(static::$aMajor[$i]);
                $owasp->{"setA" . ($i + 1) . "Info"}(static::$aInfo[$i]);
                $owasp->{"setA" . ($i + 1) . "Minor"}(static::$aMinor[$i]);
            }

            $owasp->setUtilisateurCollecte(static::$utilisateurCollecte);
            $owasp->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
            $owasp->setModeCollecte($mode);

            $manager->persist($owasp);
        }

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }

}
