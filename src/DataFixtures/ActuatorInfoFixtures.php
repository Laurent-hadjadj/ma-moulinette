<?php

namespace App\DataFixtures;

use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActuatorInfoFixtures]
 */
class ActuatorInfoFixtures extends Fixture
{
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
        $actuator01=(new Actuator)
            ->setId(1)
            ->setMavenKey('fr.ma-moulinette:app1')
            ->setNomApplication('Application 01')
            ->setUrl('http://ma-moulinette.fr/app01')
            ->setActuatorUser('user1')
            ->setActuatorPassword('password1')
            ->setPersonne('John Doe');
        $manager->persist($actuator01);
        $manager->flush();

        $actuatorInfo01=(new ActuatorInfo())
            ->setActuator($actuator01)
            ->setActuatorInfoDescription('[SOCLE][ARCHETYPE]')
            ->setActuatorInfoValue('socle.archetype');
        $manager->persist($actuatorInfo01);

        $actuator02=(new Actuator)
            ->setId(2)
            ->setMavenKey('fr.ma-moulinette:app2')
            ->setNomApplication('Application 02')
            ->setUrl('http://ma-moulinette.fr/app02')
            ->setActuatorUser('user2')
            ->setActuatorPassword('password2')
            ->setPersonne('Jane Smith');
        $manager->persist($actuator02);
        $manager->flush();

        $actuatorInfo02=(new ActuatorInfo())
            ->setActuator($actuator02)
            ->setActuatorInfoDescription('[SOCLE][ENCODAGE]')
            ->setActuatorInfoValue('socle.encodage');
        $manager->persist($actuatorInfo02);

        $actuator03=(new Actuator)
            ->setId(3)
            ->setMavenKey('fr.ma-moulinette:app3')
            ->setNomApplication('Application 03')
            ->setUrl('http://ma-moulinette.fr/app03')
            ->setActuatorUser('user3')
            ->setActuatorPassword('password3')
            ->setPersonne('Bob Johnson');
        $manager->persist($actuator03);
        $manager->flush();

        $actuatorInfo03=(new ActuatorInfo())
            ->setActuator($actuator03)
            ->setActuatorInfoDescription('[SOCLE][JAVA]')
            ->setActuatorInfoValue('socle.encodage');
        $manager->persist($actuatorInfo03);

        $actuator04=(new Actuator)
            ->setId(4)
            ->setMavenKey('fr.ma-moulinette:app4')
            ->setNomApplication('Application 04')
            ->setUrl('http://ma-moulinette.fr/app04')
            ->setActuatorUser('user4')
            ->setActuatorPassword('password4')
            ->setPersonne('Elsa Davis');
        $manager->persist($actuator04);
        $manager->flush();

        $actuatorInfo04=(new ActuatorInfo())
            ->setActuator($actuator04)
            ->setActuatorInfoDescription('[APP][JAVAMELODY]')
            ->setActuatorInfoValue('app.javamelody');
        $manager->persist($actuatorInfo04);

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }

}
