<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\unit\Repository\Main;

use App\DataFixtures\UtilisateurFixtures;
use App\Repository\Main\UtilisateurRepository;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


/**
 * [Description UtilisateurRepositorytest]
 */
class UtilisateurRepositorytest extends KernelTestCase
{
    public static $removeReturnline = "/\s+/u";

    /**
     * [Description for testCount]
     *  Compte le nombre d'utilisateur par défaut
     *
     * @return
     *
     * Created at: 01/05/2024 23:07:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testCount() {
        /**On démmare le kernel et on récupère le container */
        self::bootKernel();

        /**chargement des fixtures via lipp */
        $this->loadFixtures([UtilisateurFixtures::class]);

        $container = static::getContainer();
        $utilisateurs=$container->get(UtilisateurRepository::class)->count([]);
        $this->assertNotEquals(0, $message= "La base a été initialisée correctement");
        $this->assertEquals(1, $utilisateurs);
    }

}
