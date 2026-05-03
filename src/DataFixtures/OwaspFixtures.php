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
    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static int $referentialOwasp = 2017;
    private string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static int $effortTotal = 0;
    private static $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    public function load(ObjectManager $manager): void
    {
        $modeCollecte = ['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

        foreach ($modeCollecte as $mode) {
            $owasp = new Owasp();
            $owasp->setMavenKey(self::$mavenKey);
            $owasp->setReferentialOwasp(self::$referentialOwasp);
            $owasp->setVersion($this->version);
            $owasp->setDateVersion(new \DateTimeImmutable(self::$dateVersion));
            $owasp->setEffortTotal(self::$effortTotal);

            for ($i = 0; $i < 10; $i++) {
                $owasp->{"setA" . ($i + 1)}(self::$a[$i]);
                $owasp->{"setA" . ($i + 1) . "Blocker"}(self::$aBlocker[$i]);
                $owasp->{"setA" . ($i + 1) . "Critical"}(self::$aCritical[$i]);
                $owasp->{"setA" . ($i + 1) . "Major"}(self::$aMajor[$i]);
                $owasp->{"setA" . ($i + 1) . "Info"}(self::$aInfo[$i]);
                $owasp->{"setA" . ($i + 1) . "Minor"}(self::$aMinor[$i]);
            }

            $owasp->setUtilisateurCollecte(self::$utilisateurCollecte);
            $owasp->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
            $owasp->setModeCollecte($mode);

            $manager->persist($owasp);
        }

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }

}
