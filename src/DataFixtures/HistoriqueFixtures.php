<?php

namespace App\DataFixtures;

use App\Entity\Historique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture Historique — version alignée avec le refactor v2.0.0 de l'entité.
 *
 * Correspondances principales (old → new) :
 *   setNombreLigne        → setLines
 *   setNombreLigneCode    → setNcloc
 *   setNombreBug          → setBugs
 *   setNombreVulnerability→ setVulnerabilities
 *   setNombreCodeSmell    → setCodeSmells
 *   setNombreHotspot      → setSecurityHotspot
 *   setNombreAnomalieXxx  → setBlocker/Critical/Major/Minor/Info Violations
 *   setFrontend/Backend/Autre/inconnu → setRepartitionFrontend/Backend/Autre/Inconnu
 *   setDette              → setSqaleIndex
 *   setNoteReliability    → setReliabilityRating
 *   setNoteSecurity       → setSecurityRating
 *   setNoteSqale          → setSqaleRating
 *   setNoteHotspot        → setSecurityReviewRating
 *   setHotspot{High,Med,Low}  → setMenacePotentielleToReview{High,Medium,Low}
 *   setNoSonar            → setJavaNoSonar (+ Php/Python/Javascript/Typescript/Ruby/Xml)
 *   setTodo               → setJavaTodo   (+ Php/Python/Javascript/Typescript/Ruby/Xml)
 *   setInitial('true')    → setInitial(true)   (typage bool strict)
 */
class HistoriqueFixtures extends Fixture
{
    /**
     * Jeu de données historique pour deux versions du projet ma-moulinette.
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->rows() as $row) {
            $historique = (new Historique())
                ->setMavenKey('fr.ma-petite-entreprise:ma-moulinette')
                ->setAnalyseKey('AZCc05qWgfifxdiJPzns')
                ->setVersion($row['version'])
                ->setDateVersion($row['dateVersion'])
                ->setNomProjet('ma-moulinette')
                ->setVersionRelease($row['versionRelease'])
                ->setVersionSnapshot(0)
                ->setVersionAutre(1)
                ->setSuppressWarning(8)
                ->setJavaNoSonar(0)
                ->setJavaTodo(17)
                ->setLoggerInfo(14)
                ->setLoggerWarn(0)
                ->setLoggerError(15)
                ->setLoggerDebug(8)
                ->setLines(17049)
                ->setNcloc(8928)
                ->setFiles(180)
                ->setClasses(123)
                ->setFunctions(457)
                ->setCoverage(50.1)
                ->setDuplicatedLinesDensity(0.2)
                ->setSqaleDebtRatio(1.0)
                ->setSqaleIndex(3054)
                ->setTests(55)
                ->setViolations(295)
                ->setBugs(88)
                ->setVulnerabilities(9)
                ->setCodeSmells(198)
                ->setBugBlocker(7)
                ->setBugCritical(0)
                ->setBugMajor(44)
                ->setBugMinor(0)
                ->setBugInfo(37)
                ->setVulnerabilityBlocker(0)
                ->setVulnerabilityCritical(9)
                ->setVulnerabilityMajor(0)
                ->setVulnerabilityMinor(0)
                ->setVulnerabilityInfo(0)
                ->setCodeSmellBlocker(0)
                ->setCodeSmellCritical(4)
                ->setCodeSmellMajor(109)
                ->setCodeSmellMinor(13)
                ->setCodeSmellInfo(72)
                ->setRepartitionFrontend(21)
                ->setRepartitionBackend(136)
                ->setRepartitionAutre(0)
                ->setRepartitionInconnu($row['inconnu'])
                ->setBlockerViolations(7)
                ->setCriticalViolations(13)
                ->setMajorViolations(153)
                ->setMinorViolations(13)
                ->setInfoViolations(109)
                ->setReliabilityRating('E')
                ->setSecurityRating('D')
                ->setSqaleRating('A')
                ->setSecurityReviewRating('A')
                ->setSecurityHotspot(0)
                ->setMenacePotentielleToReviewHigh(0)
                ->setMenacePotentielleToReviewMedium(0)
                ->setMenacePotentielleToReviewLow(0)
                ->setInitial($row['initial'])
                ->setModeCollecte('COLLECTE')
                ->setUtilisateurCollecte('admin@ma-moulinette.fr')
                ->setDateEnregistrement(new \DateTimeImmutable($row['dateEnregistrement']));
            $manager->persist($historique);
        }

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'version'            => '1.2.0-RELEASE',
                'dateVersion'        => '2024-07-12 16:34:46',
                'versionRelease'     => 0,
                'inconnu'            => 10,
                'initial'            => true,
                'dateEnregistrement' => '2024-06-28 17:55:45+02',
            ],
            [
                'version'            => '1.2.3-RELEASE',
                'dateVersion'        => '2024-08-18 15:54:26',
                'versionRelease'     => 1,
                'inconnu'            => 5,
                'initial'            => false,
                'dateEnregistrement' => '2024-08-28 14:25:15+02',
            ],
        ];
    }
}
