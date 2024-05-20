<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\InformationProjet;

/**
 * [Description IsValideMavenKey]
 */
class IsValideMavenKey
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /**
     * [Description for isValide]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 20/05/2024 21:38:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isValide($mavenKey): array
    {
        /** On instancie l'entityRepository */
        $informationProjetEntity = $this->em->getRepository(InformationProjet::class);

        /** On regarde si une analyse a été réalisée. */
        $map=['maven_key'=>$mavenKey];
        $request=$informationProjetEntity->selectInformationProjetisValide($map);

        return ['code' => $request['code']];
    }
}
