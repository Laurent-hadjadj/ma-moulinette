<?php

namespace App\Repository;

use App\Entity\BatchProfiling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BatchProfilingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchProfiling::class);
    }

    public function save(BatchProfiling $profiling): void
    {
        $em = $this->getEntityManager();
        $em->persist($profiling);
        $em->flush();
    }

/*-- Voir les 10 derniers traitements toutes équipes confondues
SELECT * FROM ma_moulinette.vw_batch_profiling_stats
ORDER BY derniere_execution DESC
LIMIT 10;

-- Voir les stats pour un portefeuille donné
SELECT * FROM ma_moulinette.vw_batch_profiling_stats
WHERE portefeuille = 'TOUT LES PROJETS';

-- Performance par portefeuille sur 4 dernières semaines
SELECT portefeuille, semaine,
       AVG(temps_total_moyen_s) AS temps_moyen,
       AVG(memoire_peak_moyenne_mo) AS memoire_moyenne
FROM ma_moulinette.vw_batch_profiling_weekly
GROUP BY portefeuille, semaine
ORDER BY semaine DESC;

-- Analyse pour un utilisateur donné
SELECT * FROM ma_moulinette.vw_batch_profiling_weekly
WHERE utilisateur = 'admin@ma-moulinette.fr'
ORDER BY semaine DESC;

-- Suivi des tendances
SELECT semaine, portefeuille,
       temps_total_moyen_s,
       memoire_peak_moyenne_mo
FROM ma_moulinette.vw_batch_profiling_weekly
WHERE portefeuille = 'TOUT LES PROJETS'
ORDER BY semaine ASC;

-- Performance par mois
SELECT mois,
       AVG(temps_total_moyen_s) AS temps_moyen,
       AVG(memoire_peak_moyenne_mo) AS memoire_moyenne
FROM ma_moulinette.vw_batch_profiling_monthly
GROUP BY mois
ORDER BY mois DESC;

-- Analyse long terme pour un portefeuille
SELECT mois, temps_total_moyen_s, memoire_peak_moyenne_mo
FROM ma_moulinette.vw_batch_profiling_monthly
WHERE portefeuille = 'TOUT LES PROJETS'
ORDER BY mois ASC;

-- Suivi par utilisateur
SELECT mois, utilisateur, AVG(memoire_moyenne_mo) AS memoire_moyenne
FROM ma_moulinette.vw_batch_profiling_monthly
GROUP BY mois, utilisateur
ORDER BY mois DESC;

-- Classement des portefeuilles les plus lourds
SELECT portefeuille, AVG(memoire_peak_moyenne_mo) AS memoire_moyenne
FROM ma_moulinette.vw_batch_profiling_global
GROUP BY portefeuille
ORDER BY memoire_moyenne DESC;

-- Comparaison de performance entre utilisateurs
SELECT utilisateur, COUNT(*) AS nb_exec, AVG(temps_total_moyen_s) AS temps_moyen
FROM ma_moulinette.vw_batch_profiling_global
GROUP BY utilisateur
ORDER BY temps_moyen ASC;

-- Historique complet pour un portefeuille
SELECT * FROM ma_moulinette.vw_batch_profiling_global
WHERE portefeuille = 'TOUT LES PROJETS';

-- Tout l’historique d’un portefeuille, toutes granularités confondues
SELECT * FROM ma_moulinette.vw_batch_profiling_summary
WHERE portefeuille = 'TOUT LES PROJETS'
ORDER BY granularite, periode DESC;

-- Comparer la mémoire moyenne entre périodes
SELECT granularite, periode, memoire_moyenne_mo
FROM ma_moulinette.vw_batch_profiling_summary
WHERE utilisateur = 'admin@ma-moulinette.fr'
ORDER BY periode;

-- Comparer la mémoire moyenne entre périodes
SELECT granularite, periode, memoire_moyenne_mo
FROM ma_moulinette.vw_batch_profiling_summary
WHERE utilisateur = 'admin@ma-moulinette.fr'
ORDER BY periode;

-- Vue synthétique pour dashboard global
SELECT
    portefeuille,
    granularite,
    ROUND(AVG(temps_total_moyen_s), 2) AS temps_moyen,
    ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_moyenne
FROM ma_moulinette.vw_batch_profiling_summary
GROUP BY portefeuille, granularite
ORDER BY portefeuille, granularite;

-- 

*/

}
