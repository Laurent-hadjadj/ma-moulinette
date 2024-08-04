<?php

namespace App\DataFixtures;

use App\Entity\Actuator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActuatorFixtures]
 */
class ActuatorFixtures extends Fixture
{
    private static $date='2024-06-23 11:59:51.854783+02';

    /**
     * [Description for load]
     *
     * @param ObjectManager $manager
     *
     * @return void
     *
     * Created at: 31/07/2024 22:02:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function load(ObjectManager $manager): void
    {
        $actuator01=(new Actuator())
            ->setMavenKey('fr.ma-moulinette:app1')
            ->setNomApplication('Application 01')
            ->setUrl('http://ma-moulinette.fr/app01')
            ->setActuatorUser('user1')
            ->setActuatorPassword('password1')
            ->setPersonne('John Doe');
        $manager->persist($actuator01);

        $actuator02=(new Actuator())
            ->setMavenKey('fr.ma-moulinette:app2')
            ->setNomApplication('Application 02')
            ->setUrl('http://ma-moulinette.fr/app02')
            ->setActuatorUser('user2')
            ->setActuatorPassword('password2')
            ->setPersonne('Jane Smith');
        $manager->persist($actuator02);

        $actuator03=(new Actuator())
            ->setMavenKey('fr.ma-moulinette:app3')
            ->setNomApplication('Application 03')
            ->setUrl('http://ma-moulinette.fr/app03')
            ->setActuatorUser('user3')
            ->setActuatorPassword('password3')
            ->setPersonne('Bob Johnson');
        $manager->persist($actuator03);

        $actuator04=(new Actuator())
            ->setMavenKey('fr.ma-moulinette:app4')
            ->setNomApplication('Application 04')
            ->setUrl('http://ma-moulinette.fr/app04')
            ->setActuatorUser('user4')
            ->setActuatorPassword('password4')
            ->setPersonne('Elsa Davis');
        $manager->persist($actuator04);

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }

}
