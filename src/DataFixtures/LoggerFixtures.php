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

use App\Entity\Logger;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation LoggerFixtures.
 * Contrat : 3 lignes Logger sur la maven_key fr.ma-moulinette:ma-moulinette afin
 * de satisfaire LoggerKernelTest::testLoggerCount (assertCount(3) sur findBy maven_key)
 * et LoggerKernelTest::testLoggerFindOneBy. LoggerRepositoryTest (delete/select/insert)
 * ne contrôle que code 200.
 * Champs non-nullable : maven_key, logger_info, logger_warn, logger_error, logger_debug,
 * date_enregistrement (positionnée par le constructeur de l’entité, surchargée ici).
 */

class LoggerFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $samples = [
            ['date' => '2026-01-01 00:00:00', 'info' => 14, 'warn' => 0,  'error' => 15, 'debug' => 8],
            ['date' => '2026-02-01 00:00:00', 'info' => 18, 'warn' => 2,  'error' => 12, 'debug' => 5],
            ['date' => '2026-03-01 00:00:00', 'info' => 21, 'warn' => 1,  'error' => 9,  'debug' => 6],
        ];

        foreach ($samples as $sample) {
            $logger = new Logger(
                self::MAVEN_KEY,
                $sample['info'],
                $sample['warn'],
                $sample['error'],
                $sample['debug'],
                self::MODE_COLLECTE,
                self::UTILISATEUR_COLLECTE
            );
            $logger->setDateEnregistrement(new \DateTimeImmutable($sample['date'], $tz));

            $manager->persist($logger);
        }

        $manager->flush();
    }
}
