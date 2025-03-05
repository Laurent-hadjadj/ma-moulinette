<?php

namespace App\Repository;

use App\Entity\Repartition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Repartition>
 *
 * @method Repartition|null find($id, $lockMode = null, $lockVersion = null)
 * @method Repartition|null findOneBy(array $criteria, array $orderBy = null)
 * @method Repartition[]    findAll()
 * @method Repartition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RepartitionRepository extends ServiceEntityRepository
{
  private static $removeReturnLine = "/\s+/u";
  private static $noDataBase = 'La connexion à la base de données a échoué.';
  private static $mavenKey = ':maven_key';
  private static $setup = ':setup';
  private static $dateEnregistrement = ':date_enregistrement';
  private static $dateFormated = 'Y-m-d H:i:sO';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Repartition::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 12/02/2025 18:50:03 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
      $message = $e->getMessage();

      if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
          $message = static::$noDataBase;
      }

    // Récupération de l'exception précédente qui contient le SQLState
    $previousException = $e->getPrevious();

    if ($previousException instanceof \Doctrine\DBAL\Driver\Exception) {
        $sqlState = $previousException->getSQLState();

        // Violation de contrainte NOT NULL
        if ($sqlState === '23502') {
            return ['code' =>'23502', 'erreur' => $e->getMessage()];
        }

        // Violation de contrainte UNIQUE
        if ($sqlState === '23505') {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }
    }

      return ['code' => 500, 'erreur'=> $message];
  }

  /**
   * [Description for handleErrorException]
   *
   * @param \ErrorException $e
   *
   * @return array
   *
   * Created at: 15/02/2025 22:05:49 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleErrorException(\ErrorException $e): array
  {
      $message = $e->getMessage();
      return ['code' => 500, 'erreur'=> $message];
  }

  /**
   * [Description for selectOrUpdateRepartitionInitial]
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 14/02/2025 12:32:54 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectOrUpdateRepartitionInitial(array $map): array
  {
    $sqlCheck = "SELECT id FROM ma_moulinette.repartition
                  WHERE maven_key = :maven_key AND control = 'initial'
                  ORDER BY date_enregistrement DESC LIMIT 1";

    $sqlUpdate = "UPDATE ma_moulinette.repartition SET
                    name = :name,
                    bug_blocker = :bug_blocker,
                    bug_critical = :bug_critical,
                    bug_major = :bug_major,
                    bug_minor = :bug_minor,
                    bug_info = :bug_info,
                    vulnerability_blocker = :vulnerability_blocker,
                    vulnerability_critical = :vulnerability_critical,
                    vulnerability_major = :vulnerability_major,
                    vulnerability_minor = :vulnerability_minor,
                    vulnerability_info = :vulnerability_info,
                    code_smell_blocker = :code_smell_blocker,
                    code_smell_critical = :code_smell_critical,
                    code_smell_major = :code_smell_major,
                    code_smell_minor = :code_smell_minor,
                    code_smell_info = :code_smell_info,
                    mode_collecte = :mode_collecte,
                    setup = :setup,
                    utilisateur_collecte = :utilisateur_collecte,
                    date_enregistrement = :date_enregistrement
                  WHERE id = :id AND control = 'initial'";

    $sqlInsert = "INSERT INTO ma_moulinette.repartition (
                maven_key, name, bug_blocker, bug_critical, bug_major, bug_minor, bug_info, vulnerability_blocker, vulnerability_critical, vulnerability_major, vulnerability_minor, vulnerability_info, code_smell_blocker,code_smell_critical, code_smell_major, code_smell_minor, code_smell_info, setup, mode_collecte, utilisateur_collecte,date_enregistrement)
                VALUES (:maven_key, :name, :bug_blocker, :bug_critical, :bug_major, :bug_minor, :bug_info, :vulnerability_blocker, :vulnerability_critical, :vulnerability_major, :vulnerability_minor, :vulnerability_info, :code_smell_blocker, :code_smell_critical, :code_smell_major, :code_smell_minor, :code_smell_info, :setup, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";

    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
          // Vérification d'existence
          $stmtCheck = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlCheck));
            $stmtCheck->bindValue(static::$mavenKey, $map['maven_key']);
            $result = $stmtCheck->executeQuery();
            $existingId = $result->fetchOne();

          if ($existingId) {
              $stmtUpdate = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlUpdate));
                $stmtUpdate->bindValue(':name', $map['name']);
                $stmtUpdate->bindValue(static::$setup, $map['setup']);
                $stmtUpdate->bindValue(':bug_blocker', $map['bug_blocker']);
                $stmtUpdate->bindValue(':bug_critical', $map['bug_critical']);
                $stmtUpdate->bindValue(':bug_major', $map['bug_major']);
                $stmtUpdate->bindValue(':bug_minor', $map['bug_minor']);
                $stmtUpdate->bindValue(':bug_info', $map['bug_info']);

                $stmtUpdate->bindValue(':vulnerability_blocker', $map['vulnerability_blocker']);
                $stmtUpdate->bindValue(':vulnerability_critical', $map['vulnerability_critical']);
                $stmtUpdate->bindValue(':vulnerability_major', $map['vulnerability_major']);
                $stmtUpdate->bindValue(':vulnerability_minor', $map['vulnerability_minor']);
                $stmtUpdate->bindValue(':vulnerability_info', $map['vulnerability_info']);

                $stmtUpdate->bindValue(':code_smell_blocker', $map['code_smell_blocker']);
                $stmtUpdate->bindValue(':code_smell_critical', $map['code_smell_critical']);
                $stmtUpdate->bindValue(':code_smell_major', $map['code_smell_major']);
                $stmtUpdate->bindValue(':code_smell_minor', $map['code_smell_minor']);
                $stmtUpdate->bindValue(':code_smell_info', $map['code_smell_info']);
                $stmtUpdate->bindValue(':mode_collecte', $map['mode_collecte']);
                $stmtUpdate->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
                $stmtUpdate->bindValue(static::$dateEnregistrement, $map['date_enregistrement']->format(static::$dateFormated));
                $stmtUpdate->bindValue('id', $existingId);
                $stmtUpdate->executeStatement();
              $this->getEntityManager()->getConnection()->commit();
              return ['code' => 200, 'erreur' => 'Mise à jour effectuée.'];
          }

          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlInsert));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $stmt->bindValue(':name', $map['name']);
            $stmt->bindValue(static::$setup, $map['setup']);

            $stmt->bindValue(':bug_blocker', $map['bug_blocker']);
            $stmt->bindValue(':bug_critical', $map['bug_critical']);
            $stmt->bindValue(':bug_major', $map['bug_major']);
            $stmt->bindValue(':bug_minor', $map['bug_minor']);
            $stmt->bindValue(':bug_info', $map['bug_info']);

            $stmt->bindValue(':vulnerability_blocker', $map['vulnerability_blocker']);
            $stmt->bindValue(':vulnerability_critical', $map['vulnerability_critical']);
            $stmt->bindValue(':vulnerability_major', $map['vulnerability_major']);
            $stmt->bindValue(':vulnerability_minor', $map['vulnerability_minor']);
            $stmt->bindValue(':vulnerability_info', $map['vulnerability_info']);

            $stmt->bindValue(':code_smell_blocker', $map['code_smell_blocker']);
            $stmt->bindValue(':code_smell_critical', $map['code_smell_critical']);
            $stmt->bindValue(':code_smell_major', $map['code_smell_major']);
            $stmt->bindValue(':code_smell_minor', $map['code_smell_minor']);
            $stmt->bindValue(':code_smell_info', $map['code_smell_info']);

            $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
            $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
            $stmt->bindValue(static::$dateEnregistrement, $map['date_enregistrement']->format(static::$dateFormated));
            $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
      } catch(\ErrorException $e){
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleErrorException($e);
    }

    return ['code' => 200, 'erreur' => ''];
  }

  public function updateRepartition(array $map): array
  {
    // Requête pour récupérer l'ID de l'enregistrement concerné
    $sqlCheck = "SELECT id
                FROM ma_moulinette.repartition
                WHERE maven_key = :maven_key
                AND setup = :setup
                AND control = 'initial'
                ORDER BY date_enregistrement DESC LIMIT 1";

    /*
    * Préparation de la requête UPDATE.
    * On liste TOUTES les colonnes que l'on souhaite mettre à jour.
    * il y a 4 valeurs par sévérité pour par type (BUG, VULNERABILITY et CODE_SMELL).
    */
    $fieldsBug = [
      // BUG
      'frontend_bug_blocker', 'backend_bug_blocker', 'autre_bug_blocker', 'inconnue_bug_blocker',
      'frontend_bug_critical', 'backend_bug_critical', 'autre_bug_critical', 'inconnue_bug_critical',
      'frontend_bug_major', 'backend_bug_major', 'autre_bug_major', 'inconnue_bug_major',
      'frontend_bug_minor', 'backend_bug_minor', 'autre_bug_minor', 'inconnue_bug_minor',
      'frontend_bug_info', 'backend_bug_info', 'autre_bug_info', 'inconnue_bug_info',
    ];

    $fieldsVulnerability = [
        // VULNERABILITY
        'frontend_vulnerability_blocker', 'backend_vulnerability_blocker', 'autre_vulnerability_blocker', 'inconnue_vulnerability_blocker',
        'frontend_vulnerability_critical', 'backend_vulnerability_critical', 'autre_vulnerability_critical', 'inconnue_vulnerability_critical',
        'frontend_vulnerability_major', 'backend_vulnerability_major', 'autre_vulnerability_major', 'inconnue_vulnerability_major',
        'frontend_vulnerability_minor', 'backend_vulnerability_minor', 'autre_vulnerability_minor', 'inconnue_vulnerability_minor',
        'frontend_vulnerability_info', 'backend_vulnerability_info', 'autre_vulnerability_info', 'inconnue_vulnerability_info',
    ];

    $fieldsCodeSmell = [
        // CODE_SMELL
        'frontend_code_smell_blocker', 'backend_code_smell_blocker', 'autre_code_smell_blocker', 'inconnue_code_smell_blocker',
        'frontend_code_smell_critical', 'backend_code_smell_critical', 'autre_code_smell_critical', 'inconnue_code_smell_critical',
        'frontend_code_smell_major', 'backend_code_smell_major', 'autre_code_smell_major', 'inconnue_code_smell_major',
        'frontend_code_smell_minor', 'backend_code_smell_minor', 'autre_code_smell_minor', 'inconnue_code_smell_minor',
        'frontend_code_smell_info', 'backend_code_smell_info',
        'autre_code_smell_info', 'inconnue_code_smell_info',
    ];

    // Fusion de tous les champs dans un seul tableau
    $allFields = array_merge($fieldsBug, $fieldsVulnerability, $fieldsCodeSmell);

    // Construction dynamique de la partie SET de la requête
    $setParts = [];

    // Ajout de tous les champs des trois types
    foreach ($allFields as $field) {
        $setParts[] = "$field = :$field";
    }

    // Ajout des autres colonnes obligatoires
    $setParts[] = "control = :control";
    $setParts[] = "date_enregistrement = :date_enregistrement";

    $sqlUpdate = "UPDATE ma_moulinette.repartition SET " . implode(", ", $setParts) . "
                  WHERE id = :id
                  AND control = 'initial'
                  AND setup = :setup";

    try {
        $this->getEntityManager()->getConnection()->beginTransaction();
        // Vérification de l'existence de l'enregistrement
        $stmtCheck = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlCheck));
          $stmtCheck->bindValue(':maven_key', $map['maven_key']);
          $stmtCheck->bindValue(static::$setup, $map['setup']);
          $result = $stmtCheck->executeQuery();
        $existingId = $result->fetchOne();

        if ($existingId) {
            $stmtUpdate = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlUpdate));

            // Pour chaque champ des 3 types, vérification dans $map,
            // et affectation de -1 si la donnée n'est pas présente
            foreach ($allFields as $field) {
                $value = isset($map[$field]) ? $map[$field] : -1;
                $stmtUpdate->bindValue(":$field", $value);
            }

            // Liaison des autres paramètres obligatoires
            $stmtUpdate->bindValue(':control', $map['control']);
            $stmtUpdate->bindValue(static::$dateEnregistrement, $map['date_enregistrement']->format(static::$dateFormated));
            $stmtUpdate->bindValue(':id', $existingId);
            $stmtUpdate->bindValue(static::$setup, $map['setup']);

            // Exécution de la mise à jour
            $stmtUpdate->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        }
    } catch (\Doctrine\DBAL\Exception $e) {
      $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    } catch (\ErrorException $e) {
      $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleErrorException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }
}
