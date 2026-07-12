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

use App\Entity\Mesures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation MesuresFixtures.
 * Contrat : 3 lignes Mesures partageant la même maven_key
 * (fr.ma-moulinette:ma-moulinette) afin de satisfaire MesuresKernelTest
 * (findOneBy maven_key + assertCount(3, findBy maven_key)) et MesuresRepositoryTest
 * (selectMesuresVersionLast / insertMesures / deleteMesuresMavenKey -> code 200).
 * Tous les champs non-nullable de l’entité (maven_key, project_name, date_enregistrement)
 * sont renseignes ; les autres colonnes (22 au total après l'audit DDL du 2026-05-05)
 * gardent leur valeur par défaut nullable.
 */

class MesuresFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const PROJECT_NAME = 'Ma-Moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';
    private const TIME_ZONE = 'Europe/Paris';
    private const ISSUE = 'TOTAL=0;HIGH=0;MEDIUM=0;LOW=0;INFO=0';

    public function load(ObjectManager $manager): void
    {
        $dates = [
            new \DateTimeImmutable('2026-01-01 10:00:00', new \DateTimeZone(self::TIME_ZONE)),
            new \DateTimeImmutable('2026-02-01 10:00:00', new \DateTimeZone(self::TIME_ZONE)),
            new \DateTimeImmutable('2026-03-01 10:00:00', new \DateTimeZone(self::TIME_ZONE)),
        ];

        foreach ($dates as $index => $date) {
            $mesure = (new Mesures())
                ->setMavenKey(self::MAVEN_KEY)
                ->setProjectName(self::PROJECT_NAME)
                ->setAlertStatus('OK')
                ->setLines(22015)
                ->setNcloc(10043)
                ->setNclocLanguageDistribution('java=4278;ts=18690')
                ->setFiles(180)
                ->setClasses(226)
                ->setFunctions(52)
                ->setStatements(0)
                ->setCommentLines(0)
                ->setCommentLinesDensity(0.0)
                ->setCommentLinesRating('A')
                ->setCoverage(10.3)
                ->setBranchCoverage(0.0)
                ->setLineCoverage(0.0)
                ->setLinesToCover(0)
                ->setConditionsToCover(0)
                ->setUncoveredConditions(0)
                ->setCoverageRating('A')
                ->setTests(0)
                ->setTestExecutionTime(0)
                ->setTestErrors(0)
                ->setTestFailures(0)
                ->setSkippedTests(0)
                ->setTestSuccessDensity(0.0)
                ->setDuplicatedFiles(0)
                ->setDuplicatedBlocks(0)
                ->setDuplicatedLines(0)
                ->setDuplicatedLinesDensity(0.0)
                ->setDuplicatedLinesRating('A')
                ->setComplexity(0)
                ->setComplexityRating('A')
                ->setCognitiveComplexity(0)
                ->setCognitiveComplexityRating('A')
                ->setComplexityRatio(0.0)
                ->setCognitiveComplexityRatio(0.0)
                ->setOpenIssues(0)
                ->setReopenedIssues(0)
                ->setConfirmedIssues(0)
                ->setFalsePositiveIssues(0)
                ->setAcceptedIssues(0)
                ->setHighImpactAcceptedIssues(0)
                ->setViolations(0)
                ->setBlockerViolations(0)
                ->setCriticalViolations(0)
                ->setMajorViolations(0)
                ->setMinorViolations(0)
                ->setInfoViolations(0)
                ->setSoftwareQualityBlockerIssues(0)
                ->setSoftwareQualityHighIssues(0)
                ->setSoftwareQualityMediumIssues(0)
                ->setSoftwareQualityLowIssues(0)
                ->setSoftwareQualityInfoIssues(0)
                ->setCodeSmells(0)
                ->setMaintainabilityIssues(self::ISSUE)
                ->setSqaleIndex(0)
                ->setSqaleDebtRatio(0.0)
                ->setSqaleRating('A')
                ->setEffortToReachMaintainabilityRatingA(0)
                ->setSoftwareQualityMaintainabilityIssues(self::ISSUE)
                ->setSoftwareQualityMaintainabilityRating('A')
                ->setSoftwareQualityMaintainabilityDebtRatio(0.0)
                ->setSoftwareQualityMaintainabilityRemediationEffort(0)
                ->setEffortToReachSoftwareQualityMaintainabilityRatingA(0)
                ->setBugs(0)
                ->setReliabilityIssues(self::ISSUE)
                ->setReliabilityRating('A')
                ->setReliabilityRemediationEffort(0)
                ->setSoftwareQualityReliabilityIssues(self::ISSUE)
                ->setSoftwareQualityReliabilityRating('A')
                ->setSoftwareQualityReliabilityRemediationEffort(0)
                ->setVulnerabilities(0)
                ->setSecurityIssues(self::ISSUE)
                ->setSecurityRating('A')
                ->setSecurityRemediationEffort(0)
                ->setSoftwareQualitySecurityIssues(self::ISSUE)
                ->setSoftwareQualitySecurityRating('A')
                ->setSoftwareQualitySecurityRemediationEffort(0)
                ->setSecurityHotspot(0)
                ->setSecurityReviewRating('A')
                ->setSecurityHotspotsReviewed(0.0)
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($date);

            $manager->persist($mesure);
        }

        $manager->flush();
    }
}
