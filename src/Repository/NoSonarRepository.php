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

 namespace App\Repository;

use App\Entity\NoSonar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NoSonar|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoSonar|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoSonar[]    findAll()
 * @method NoSonar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoSonarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoSonar::class);
    }
}
