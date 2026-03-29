<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service\CommandRebuildHistorique;

/**
 * [Description SonarMetricsCatalogService]
 */
class SonarMetricsCatalogService
{

    /**
     * [Description for metricsDefinition]
     *
     * @return array
     *
     * Created at: 19/03/2026 17:29:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function metricsDefinition(): array
    {
        return [
            'lines' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre total de lignes du projet, incluant code, commentaires et lignes vides. Cette métrique donne une vision globale de la taille du code source. Elle permet de suivre l’évolution volumétrique du projet dans le temps."
            ],

            'ncloc' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de lignes de code effectives, excluant commentaires et lignes vides. Elle représente la quantité réelle de logique métier. Sert de base pour de nombreux indicateurs comme la couverture ou la dette technique."
            ],

            'ncloc_language_distribution' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'string',
                'description' => "Répartition des lignes de code par langage de programmation. Permet d’identifier les technologies dominantes du projet. Utile pour analyser la diversité technique et orienter les compétences nécessaires."
            ],

            'files' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre total de fichiers analysés dans le projet. Reflète la structure et la granularité du code. Peut être utilisé pour évaluer la complexité organisationnelle."
            ],

            'classes' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de classes définies dans le code. Indique le niveau d’abstraction et de modularité. Une augmentation peut traduire une architecture plus structurée ou plus complexe."
            ],

            'functions' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de fonctions ou méthodes présentes dans le code. Permet d’évaluer la granularité des traitements. Une forte densité peut indiquer un bon découpage ou une complexité excessive."
            ],

            'statements' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre total d’instructions exécutables dans le code. Représente la quantité réelle de logique exécutée. Sert notamment au calcul de la couverture de tests."
            ],

            'comment_lines' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de lignes contenant des commentaires. Les commentaires facilitent la compréhension du code. Un bon équilibre est essentiel pour la maintenabilité."
            ],

            'comment_lines_density' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Pourcentage de commentaires par rapport au code total. Permet d’évaluer la qualité de la documentation interne. Un ratio équilibré améliore la lisibilité."
            ],

            'comment_lines_rating' => [
                'domaine' => 'ma-moulinette',
                'version' => 8,
                'type' => 'string',
                'description' => 'Note commentaire (A-E)'
            ],

            'coverage' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Pourcentage global de couverture du code par les tests. Mesure la part de code exécutée pendant les tests. Un taux élevé améliore la confiance dans la qualité du code."
            ],
            'coverage_rating' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => 'Note de couverture des tests. Permet d’évaluer la qualité de la couverture du code. Une note élevée indique une bonne couverture, tandis qu’une note basse peut signaler des zones non testées.'
            ],

            'branch_coverage' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Couverture des conditions logiques du code (if, switch). Vérifie que toutes les branches sont testées. Réduit les risques de comportements inattendus."
            ],

            'line_coverage' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Pourcentage de lignes exécutées par les tests. Donne une vision simple de la couverture. Complémentaire à la couverture des branches."
            ],

            'lines_to_cover' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de lignes de code devant être couvertes par les tests. Sert de base au calcul de la couverture. Permet d’identifier les zones critiques."
            ],

            'conditions_to_cover' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre total de conditions à tester. Inclut les branches logiques du code. Plus ce nombre est élevé, plus les tests doivent être complets."
            ],

            'uncovered_conditions' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de conditions non couvertes par les tests. Indique les zones de risque. Doit être réduit pour améliorer la qualité."
            ],

            'tests' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre total de tests unitaires exécutés. Reflète la stratégie de test du projet. Plus il est élevé, meilleure est la couverture potentielle."
            ],

            'test_execution_time' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'MILLISEC',
                'description' => "Durée totale d’exécution des tests unitaires. Permet d’évaluer leur performance. Un temps trop long peut ralentir les cycles de développement."
            ],

            'test_errors' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’erreurs techniques lors de l’exécution des tests. Ces erreurs empêchent leur bon déroulement. Elles doivent être corrigées rapidement."
            ],

            'test_failures' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de tests ayant échoué. Indique des anomalies fonctionnelles ou régressions. Doit être proche de zéro pour garantir la qualité."
            ],

            'skipped_tests' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de tests ignorés lors de l’exécution. Ces tests ne contribuent pas à la couverture. Un nombre élevé peut masquer des risques."
            ],

            'test_success_density' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Pourcentage de tests réussis. Mesure la stabilité de la suite de tests. Un taux élevé indique un code fiable."
            ],

            'duplicated_blocks' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de blocs de code dupliqués. Indique la présence de copier-coller. La duplication augmente la dette technique."
            ],

            'duplicated_files' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de fichiers contenant des duplications. Permet d’identifier les zones redondantes. Impacte la maintenabilité."
            ],

            'duplicated_lines' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de lignes dupliquées dans le code. Représente le volume de duplication. Plus il est élevé, plus le risque de maintenance est important."
            ],

            'duplicated_lines_density' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Pourcentage de duplication dans le code. Mesure la proportion de code copié. Une valeur élevée dégrade la qualité globale."
            ],
            'duplicated_lines_rating' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => "Note de duplication des lignes. Permet d’évaluer la qualité de la gestion des duplications. Une note élevée indique un code avec beaucoup de lignes dupliquées, ce qui peut compliquer la maintenance."
            ],

            'open_issues' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues ouvertes. Représente les problèmes en attente de correction. Permet de suivre la dette en cours."
            ],

            'reopened_issues' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues réouvertes après correction. Indique des corrections insuffisantes. Peut révéler un manque de qualité dans les fix."
            ],

            'confirmed_issues' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues confirmées comme valides. Exclut les faux positifs. Représente les problèmes réels."
            ],

            'accepted_issues' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Nombre d’issues acceptées sans correction. Ces problèmes sont connus mais jugés non prioritaires. Ils restent dans le code."
            ],

            'high_impact_accepted_issues' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Nombre d’issues acceptées avec un impact élevé (blocker ou high). Représente un risque assumé. Doit être surveillé attentivement."
            ],

            'false_positive_issues' => ['domaine' => 'SonarQube', 'version' => 8, 'type' => 'int', 'description' => ''],

            'violations' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre total d’issues détectées. Inclut bugs, vulnérabilités et code smells. Donne une vision globale de la qualité."
            ],

            'blocker_violations' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité bloquante détectées dans le code. Ces problèmes représentent des risques critiques pouvant empêcher le bon fonctionnement ou la mise en production. Ils doivent être corrigés en priorité absolue."
            ],

            'critical_violations' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité critique identifiées. Ces anomalies peuvent entraîner des dysfonctionnements importants ou des comportements incorrects. Leur correction est fortement recommandée avant toute mise en production."
            ],

            'major_violations' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité majeure détectées. Elles impactent la qualité du code et sa maintenabilité sans être bloquantes. Leur traitement permet d’améliorer la robustesse globale du projet."
            ],

            'minor_violations' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité mineure présentes dans le code. Ces problèmes ont un impact limité et concernent souvent des améliorations de style ou de bonnes pratiques. Leur correction reste recommandée pour maintenir un code propre."
            ],

            'info_violations' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre d’issues informatives détectées. Elles n’impactent pas directement la qualité ou le fonctionnement du code. Elles servent principalement à signaler des optimisations ou des bonnes pratiques à suivre."
            ],

            'software_quality_blocker_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité bloquante selon le modèle de qualité logiciel récent. Ces problèmes représentent des risques critiques pouvant empêcher le fonctionnement correct ou la mise en production. Ils doivent être corrigés immédiatement."
            ],

            'software_quality_high_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité élevée détectées dans le code. Elles peuvent avoir un impact important sur la qualité, la fiabilité ou la sécurité. Leur correction est fortement prioritaire."
            ],

            'software_quality_medium_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité moyenne identifiées. Ces problèmes ont un impact modéré sur la qualité du code ou sa maintenabilité. Ils doivent être traités pour améliorer progressivement la qualité globale."
            ],

            'software_quality_low_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues de sévérité faible détectées. Elles concernent généralement des optimisations ou des améliorations mineures. Leur correction contribue à maintenir un code propre et cohérent."
            ],

            'software_quality_info_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues informatives dans le modèle de qualité logiciel. Elles n’ont pas d’impact direct sur le fonctionnement ou la qualité. Elles servent principalement à signaler des bonnes pratiques ou des améliorations possibles."
            ],

            'complexity' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Complexité cyclomatique du code. Mesure le nombre de chemins d’exécution. Une valeur élevée rend le code difficile à tester."
            ],

            'cognitive_complexity' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Complexité cognitive du code. Évalue la difficulté de compréhension humaine. Une valeur élevée indique un code difficile à lire."
            ],
            'complexity_ratio' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'float',
                'description' => "Ratio de complexité cyclomatique exprimé en pourcentage par rapport à une base de référence (par exemple le nombre de lignes de code ou de fonctions). Cette métrique permet d’évaluer le niveau global de complexité logique du projet. Un ratio élevé indique un code comportant de nombreux chemins d’exécution, donc plus difficile à tester, maintenir et sécuriser."
            ],

            'cognitive_complexity_ratio' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'float',
                'description' => "Ratio de complexité cognitive exprimé en pourcentage par rapport à une base de référence (par exemple le nombre de lignes de code ou de fonctions). Cette métrique permet d’évaluer la difficulté globale de compréhension du code à l’échelle du projet. Un ratio élevé indique un code potentiellement difficile à lire, maintenir et faire évoluer."
            ],

            'code_smells' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de mauvaises pratiques détectées. Impacte la maintenabilité sans bloquer l’exécution. Contribue à la dette technique."
            ],

            'sqale_index' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Dette technique estimée en minutes. Représente l’effort de correction total. Permet de prioriser les actions."
            ],

            'sqale_debt_ratio' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Ratio de dette technique par rapport au coût de développement. Exprimé en pourcentage. Plus il est faible, meilleure est la qualité."
            ],

            'maintainability_issues' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'string',
                'description' => "Ensemble des issues liées à la maintenabilité du code. Elles regroupent principalement les code smells impactant la lisibilité et l’évolution du projet. Ces problèmes augmentent la dette technique et rendent les modifications futures plus coûteuses."
            ],

            'software_quality_maintainability_debt_ratio' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'float',
                'description' => "Ratio de dette technique lié à la maintenabilité, exprimé en pourcentage. Il compare l’effort de correction au coût estimé de développement du code. Plus ce ratio est faible, meilleure est la qualité de maintenabilité."
            ],

            'effort_to_reach_maintainability_rating_a' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Effort estimé nécessaire pour atteindre la note A en maintenabilité. Exprimé généralement en temps (minutes ou jours). Permet d’évaluer le coût d’amélioration du code."
            ],

            'software_quality_maintainability_remediation_effort' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Effort total requis pour corriger les problèmes de maintenabilité. Représente la dette technique associée aux code smells. Sert à prioriser les actions de refactoring."
            ],

            'software_quality_maintainability_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues liées à la maintenabilité selon le modèle de qualité logiciel récent. Inclut les problèmes impactant la lisibilité et l’évolution du code. Une valeur élevée indique un besoin de refactoring."
            ],

            'effort_to_reach_software_quality_maintainability_rating_a' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Effort estimé pour atteindre la note A en maintenabilité dans le modèle software quality. Représente le coût de correction des problèmes identifiés. Permet d’anticiper les travaux d’amélioration."
            ],

            'bugs' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de bugs détectés. Représente les défauts pouvant provoquer des erreurs. Impact direct sur la fiabilité."
            ],
            'reliability_rating' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'string',
                'description' => "Note de fiabilité de A à E. Basée sur les bugs détectés. Permet d’évaluer le niveau de risque."
            ],
            'reliability_remediation_effort' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Effort estimé nécessaire pour corriger les problèmes de fiabilité du code, généralement exprimé en temps. Cette métrique correspond au coût de résolution des bugs identifiés. Elle permet de prioriser les actions visant à améliorer la stabilité du logiciel."
            ],

            'reliability_issues' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Nombre d’issues liées à la fiabilité du code, principalement les bugs. Ces problèmes peuvent provoquer des erreurs à l’exécution ou des comportements inattendus. Leur correction est essentielle pour garantir la stabilité de l’application."
            ],

            'software_quality_reliability_rating' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'string',
                'description' => "Note de fiabilité selon le modèle de qualité logiciel, allant de A à E. Elle est calculée en fonction du nombre et de la sévérité des bugs détectés. Une note élevée indique un faible risque de défaillance en production."
            ],

            'software_quality_reliability_remediation_effort' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Effort total requis pour corriger les problèmes de fiabilité identifiés. Représente le coût de résolution des bugs dans le modèle software quality. Permet d’anticiper les travaux nécessaires pour améliorer la stabilité."
            ],

            'software_quality_reliability_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues de fiabilité selon le modèle de qualité logiciel récent. Inclut les bugs impactant le comportement du système. Une valeur élevée indique un risque accru d’erreurs en production."
            ],


            'vulnerabilities' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de vulnérabilités de sécurité détectées. Ces failles peuvent être exploitées. Doivent être corrigées rapidement."
            ],

            'security_rating' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'string',
                'description' => "Note de sécurité de A à E. Basée sur les vulnérabilités. Indique le niveau de risque global."
            ],

            'security_remediation_effort' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Effort estimé nécessaire pour corriger les vulnérabilités de sécurité détectées dans le code. Exprimé généralement en temps, il représente le coût de mise en conformité. Cette métrique aide à prioriser les actions de sécurisation du projet."
            ],

            'software_quality_security_rating' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'string',
                'description' => "Note de sécurité du projet selon le modèle software quality, allant de A à E. Elle dépend du nombre et de la sévérité des vulnérabilités identifiées. Une note élevée indique un faible niveau de risque en matière de sécurité."
            ],

            'security_issues' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'string',
                'description' => "Ensemble des problèmes de sécurité détectés dans le code, incluant les vulnérabilités. Ces issues peuvent exposer l’application à des attaques ou des failles exploitables. Leur analyse et correction sont essentielles pour protéger le système."
            ],

            'software_quality_security_remediation_effort' => [
                'domaine' => 'SonarQube',
                'version' => 10,
                'type' => 'int',
                'description' => "Effort total requis pour corriger les problèmes de sécurité selon le modèle software quality. Représente le coût global de remédiation des vulnérabilités. Permet d’évaluer l’investissement nécessaire pour sécuriser l’application."
            ],

            'software_quality_security_issues' => [
                'domaine' => 'SonarQube',
                'version' => 2024,
                'type' => 'int',
                'description' => "Nombre d’issues de sécurité identifiées dans le modèle de qualité logiciel récent. Inclut les vulnérabilités pouvant compromettre le système. Une valeur élevée indique un niveau de risque important."
            ],

            'security_hotspots' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'int',
                'description' => "Nombre de Security Hotspots identifiés, c’est-à-dire des zones de code sensibles nécessitant une revue manuelle. Ces éléments ne sont pas forcément des vulnérabilités mais peuvent le devenir selon le contexte. Ils doivent être analysés par un développeur ou un expert sécurité."
            ],

            'security_hotspots_reviewed' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'float',
                'description' => "Pourcentage de Security Hotspots ayant été examinés et validés. Indique le niveau de couverture des revues de sécurité manuelles. Un taux élevé signifie que les zones sensibles ont été correctement analysées."
            ],

            'security_review_rating' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'string',
                'description' => "Note de revue des Security Hotspots, allant de A à E. Elle reflète la qualité et le niveau de complétude des analyses effectuées sur les zones sensibles. Une bonne note indique un processus de revue sécurité maîtrisé."
            ],

            'alert_status' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'string',
                'description' => "Statut du Quality Gate. Indique si les critères qualité sont respectés. Conditionne souvent la mise en production."
            ],

            'mode_collecte' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => 'Type de collecte : REBUILD | COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE.'
            ],
            'utilisateur_collecte' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => 'Auteur de la collecte de données.'
            ],
            'date_enregistrement' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => "date de l’événement."
            ],
            'maven_key' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => 'Clé unique du projet, souvent project_key (fr.monapplication:ma-moulinette).'
                ],
            'analyse_key' => [
                'domaine' => 'SonarQube',
                'version' => 8,
                'type' => 'string',
                'description' => "Identifiant unique d’une analyse SonarQube. Permet de tracer un scan précis. Utile pour historiser les résultats."
            ],
            'version' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => 'Version du projet.'
                ],
            'date_version' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'description' => "Date de l'analyse du projet"],
            'nom_projet' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'string',
                'Nom du projet i.e. extrait de la maven_key.'],
            'version_release' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de version de type RELEASE.'],
            'version_snapshot' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de version de type SNAPSHOT.'],
            'version_autre' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de version de type RC, ALPHA, BETA,...'],
            'java_no_sonar' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de noSonar pour JAVA (java:S1309).'],
            'python_no_sonar' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de noSonar pour PYTHON (python:S1309).'],
            'php_no_sonar' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de noSonar pour PHP (php:S1309).'],
            'check_style' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de checkstyle:off pour JAVA (java:S1315).'],
            'no_pmd' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de noPMD pour JAVA (java:S1310).'],
            'suppress_warning' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de suppressWarning pour JAVA (java:S1309).'],
            'javascript_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour javascript (javascript:S1135).'],
            'xml_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour xml (xml:S1135).'],
            'typescript_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour typescript (typescript:S1135).'],
            'web_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour web (web:S1135).'],
            'java_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour java (java:S1135).'],
            'php_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour php (php:S1135).'],
            'ruby_todo' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de todo pour ruby (ruby:S1135).'],
            'python_todo' => [
                'domaine' =>
                'ma-moulinette',
                'version' => null,
                'type' => 'int', 'Nombre de todo pour python (python:S1135).'],
            'logger_info' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de logger de type INFO.'],
            'logger_warn' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de logger de type WARN.'],
            'logger_error' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de logger de type ERROR.'],
            'logger_debug' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de logger de type DEBUG.'],
            'repartition_frontend' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de signalement FRONTEND.'],
            'repartition_backend' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de signalement BACKEND.'],
            'repartition_autre' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de signalement AUTRE (autre modules).'],
            'repartition_inconnu' => [
                'domaine' => 'ma-moulinette',
                'version' => null,
                'type' => 'int',
                'Nombre de signalement INCONNU.'],

        ];
    }

}
