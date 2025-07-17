<?php

namespace App\Tests\Unit\Repository;

use App\DataFixtures\RepartitionFixtures;
use App\Entity\Repartition;
use App\Repository\RepartitionRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RepartitionRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    private static $setup = 1000000000000;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->purgeDatabase();
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em?->close();
        $this->em = null;
    }

    private function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $purger->purge();
    }

    /**
     * [Description for loadFixtures]
     *
     * @return void
     *
     * Created at: 06/07/2025 12:07:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function loadFixtures(): void
    {
        $fixture = new RepartitionFixtures();
        $fixture->load($this->em);
    }

    /**
     * [Description for getRepository]
     *
     * @return RepartitionRepository
     *
     * Created at: 06/07/2025 12:07:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getRepository(): RepartitionRepository
    {
        return $this->em->getRepository(Repartition::class);
    }

    /**
     * [Description for buildCompleteMap]
     *
     * @return array
     *
     * Created at: 06/07/2025 12:07:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildCompleteMap(): array {
        $defaultFields = ['bug_blocker', 'bug_critical', 'bug_major', 'bug_minor', 'bug_info',
            'vulnerability_blocker', 'vulnerability_critical', 'vulnerability_major', 'vulnerability_minor', 'vulnerability_info',
            'code_smell_blocker', 'code_smell_critical', 'code_smell_major', 'code_smell_minor', 'code_smell_info',
            'frontend', 'backend',  'autre','inconnue'
        ];

        $fieldsBug = [
            'frontend_bug_blocker', 'backend_bug_blocker', 'autre_bug_blocker', 'inconnue_bug_blocker',
            'frontend_bug_critical', 'backend_bug_critical', 'autre_bug_critical', 'inconnue_bug_critical',
            'frontend_bug_major', 'backend_bug_major', 'autre_bug_major', 'inconnue_bug_major',
            'frontend_bug_minor', 'backend_bug_minor', 'autre_bug_minor', 'inconnue_bug_minor',
            'frontend_bug_info', 'backend_bug_info', 'autre_bug_info', 'inconnue_bug_info',
        ];

        $fieldsVulnerability = [
            'frontend_vulnerability_blocker', 'backend_vulnerability_blocker', 'autre_vulnerability_blocker', 'inconnue_vulnerability_blocker',
            'frontend_vulnerability_critical', 'backend_vulnerability_critical', 'autre_vulnerability_critical', 'inconnue_vulnerability_critical',
            'frontend_vulnerability_major', 'backend_vulnerability_major', 'autre_vulnerability_major', 'inconnue_vulnerability_major',
            'frontend_vulnerability_minor', 'backend_vulnerability_minor', 'autre_vulnerability_minor', 'inconnue_vulnerability_minor',
            'frontend_vulnerability_info', 'backend_vulnerability_info', 'autre_vulnerability_info', 'inconnue_vulnerability_info',
        ];

        $fieldsCodeSmell = [
            'frontend_code_smell_blocker', 'backend_code_smell_blocker', 'autre_code_smell_blocker', 'inconnue_code_smell_blocker',
            'frontend_code_smell_critical', 'backend_code_smell_critical', 'autre_code_smell_critical', 'inconnue_code_smell_critical',
            'frontend_code_smell_major', 'backend_code_smell_major', 'autre_code_smell_major', 'inconnue_code_smell_major',
            'frontend_code_smell_minor', 'backend_code_smell_minor', 'autre_code_smell_minor', 'inconnue_code_smell_minor',
            'frontend_code_smell_info', 'backend_code_smell_info', 'autre_code_smell_info', 'inconnue_code_smell_info',
        ];

        $allFields = array_merge($defaultFields, $fieldsBug, $fieldsVulnerability, $fieldsCodeSmell);

        // Construction du tableau avec valeurs par défaut (0)
        $map = [];
        foreach ($allFields as $field) {
            $map[$field] = 0;
        }

        // Valeurs essentielles à remplir pour l'appel
        $map['maven_key'] = 'ma-cle-maven';
        $map['name'] = 'ma-moulinette';
        $map['setup'] = static::$setup;
        $map['mode_collecte'] = 'COLLECTE';
        $map['utilisateur_collecte'] = 'mOi';
        $map['control'] = 'initial';
        $map['date_enregistrement'] = new \DateTime('2025-02-17 19:13:59');
        return $map;
    }

    /**
     * [Description for testInitialInsert]
     *
     * @return void
     *
     * Created at: 06/07/2025 12:07:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testInitialInsert(): void
    {
        $repo = $this->getRepository();
        $map = $this->buildCompleteMap();

        $result = $repo->selectOrUpdateRepartitionInitial($map);

        $this->assertEquals(200, $result['code']);
        $this->assertEmpty($result['erreur'] ?? '');

        $repartition = $repo->findOneBy(['mavenKey' => $map['maven_key']]);
        $this->assertNotNull($repartition);
        $this->assertEquals('ma-moulinette', $repartition->getName());
    }

    /**
     * [Description for testUpdateExistingRepartition]
     *
     * @return void
     *
     * Created at: 06/07/2025 12:07:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testUpdateExistingRepartition(): void
    {
        $map = $this->buildCompleteMap();
        // Première insertion
        $repo = $this->getRepository();
        $repo->selectOrUpdateRepartitionInitial($map);

        // Mise à jour avec nouveau nom
        $map['name'] = 'update-ma-moulinette';
        $result = $repo->selectOrUpdateRepartitionInitial($map);
        $this->assertEquals(200, $result['code']);
        $this->assertNotEmpty($result['erreur'] ?? '', 'Le champ "erreur" est vide ou non défini.');
        $this->assertSame('Mise à jour effectuée.', $result['erreur'], 'Le message de mise à jour est incorrect.');

        $repartition = $repo->findOneBy(['mavenKey' => $map['maven_key']]);
        $this->assertNotNull($repartition);
        $this->assertEquals('update-ma-moulinette', $repartition->getName());
    }


    /**
     * [Description for testDirectUpdateRepartition]
     *
     * @return void
     *
     * Created at: 06/07/2025 12:07:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testDirectUpdateRepartition(): void
    {
        $repo = $this->getRepository();

        $now = new \DateTimeImmutable();
        $name = 'test-setup';
        $map = $this->buildCompleteMap();
        $mavenKey = $map['maven_key'];

        $repo->selectOrUpdateRepartitionInitial($map);

        // Création du tableau de mise à jour
        $updateMap = [
            'maven_key' => $mavenKey,
            'setup' => $map['setup'],
            'name' => $name,
            'control' => 'initial',
            'date_enregistrement' => $now
        ];

        $result = $repo->updateRepartition($updateMap);
        $this->assertEquals(200, $result['code']);
    }
}
