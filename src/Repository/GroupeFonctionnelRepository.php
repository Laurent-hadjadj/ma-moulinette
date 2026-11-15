<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Repository;

use App\Entity\GroupeFonctionnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupeFonctionnel>
 *
 * @method GroupeFonctionnel|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupeFonctionnel|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method GroupeFonctionnel[]    findAll()
 * @method GroupeFonctionnel[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class GroupeFonctionnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeFonctionnel::class);
    }

}
