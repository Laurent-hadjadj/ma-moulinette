<?php

namespace App\DataFixtures;

use App\Entity\OwaspTop10;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description OwaspTop10Fixtures]
 */
class OwaspTop10Fixtures extends Fixture
{
    private static $year = 2017;
    private static $category = "A1 - Attaques d'injection";
    private static $description = "Les failles d'injection, telles que l'injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d'une commande ou d'une requête. Les données hostiles de l'attaquant peuvent inciter l'interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.";
    private static $lien = '__a01-2017-injection.html.twig';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';


    /**
     * [Description for load]
     *
     * @param ObjectManager $manager
     *
     * @return void
     *
     * Created at: 02/01/2025 18:28:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function load(ObjectManager $manager): void
    {
        $owaspTop10=(new OwaspTop10())
            ->setYear(static::$year)
            ->setCategory(static::$category)
            ->setDescription(static::$description)
            ->setLien(static::$lien)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($owaspTop10);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }

}
