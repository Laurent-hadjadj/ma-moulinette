<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\FileLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Cesargb\Log\Rotation;

/**
 * [Description FileLoggerTest]
 */
class FileLoggerTest extends TestCase
{
    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
    private $params;
    /** @var \App\Service\FileLogger */
    private $fileLogger;
    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $filesystem;
    /** @var \Symfony\Component\Finder\Finder */
    private $finder;
    /** @var \Cesargb\Log\Rotation */
    private $rotation;
    private string $auditPath;
    private string $projectDir;
    private string $fullAuditPath;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->finder = $this->createMock(Finder::class);
        $this->rotation = $this->createMock(Rotation::class);

        // Définition de la racine du projet et du dossier pour les fichiers
        $this->projectDir = realpath(__DIR__ . '/../..');
        $this->auditPath = '/Fixtures/';

      $this->params->method('get')->will($this->returnValueMap([
        ['kernel.project_dir', $this->projectDir],
        ['path.audit', $this->auditPath],
      ]));

      // Stocker le chemin complet attendu
      $this->fullAuditPath = $this->projectDir . str_replace('/', DIRECTORY_SEPARATOR, $this->auditPath);

      // Initialiser FileLogger avec les paramètres simulés
      $this->fileLogger = new FileLogger($this->params, $this->filesystem, $this->finder, $this->rotation);
    }

    public function testDownloadContentWhenFileExists()
    {
      $portefeuille = "MA_MOULINETTE";
      $type = "manuel";

      // Simuler l'existence du répertoire
      $mockFilesystem = $this->createMock(Filesystem::class);
      $mockFilesystem->method('exists')->with($this->fullAuditPath)->willReturn(true);

      // Simuler Finder pour trouver le fichier
      $mockFinder = $this->createMock(Finder::class);
      $mockFinder->method('files')->willReturn($mockFinder);
      $mockFinder->method('in')->with($this->fullAuditPath)->willReturn($mockFinder);
      $mockFinder->method('name')->with("manuel_MA_MOULINETTE.log")->willReturn($mockFinder);

      // Simuler un fichier trouvé avec un contenu
      $mockFile = $this->createMock(\Symfony\Component\Finder\SplFileInfo::class);
      $mockFile->method('getPathname')->willReturn("{$this->fullAuditPath}manuel_MA_MOULINETTE.log");
      $mockFile->method('getContents')->willReturn("Contenu du fichier log");
      $mockFinder->method('getIterator')->willReturn(new \ArrayIterator([$mockFile]));

      // Appliquer le test
      $result = $this->fileLogger->downloadContent($portefeuille, $type);

      // Vérifications
      $this->assertArrayHasKey('recherche', $result);
      $this->assertArrayHasKey('content', $result);
      $this->assertEquals('OK', $result['recherche']);  // Recherche OK
      $this->assertEquals('Contenu du fichier log', $result['content']);  // Vérifie le contenu
    }

    public function testDownloadContentWhenFileIsEmpty()
    {
      $portefeuille = "MA_MOULINETTE";
      $type = "automatique";

      // Appliquer le test
      $result = $this->fileLogger->downloadContent($portefeuille, $type);

      // Vérification des assertions
      $this->assertArrayHasKey('recherche', $result);
      $this->assertArrayHasKey('content', $result);
      $this->assertEquals('Pas de journal disponible.', $result['recherche']);  // Vérifier le message
      $this->assertEquals('', $result['content']);  // Vérifier que le contenu est vide
    }

    public function testDownloadContentWhenFileDoesNotExist()
    {
        $portefeuille = "MA_MOULINETTE";
        $type = "collecte";

        // Simuler l'existence du répertoire
        $mockFilesystem = $this->createMock(Filesystem::class);
        $mockFilesystem->method('exists')->with($this->fullAuditPath)->willReturn(true);

        // Simuler Finder pour ne pas trouver de fichier
        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->method('files')->willReturn($mockFinder);
        $mockFinder->method('in')->with($this->fullAuditPath)->willReturn($mockFinder);
        $mockFinder->method('name')->with("collecte_MA_MOULINETTE.log")->willReturn($mockFinder);
        $mockFinder->method('getIterator')->willReturn(new \ArrayIterator([])); // Aucun fichier trouvé

        // Appliquer le test
        $result = $this->fileLogger->downloadContent($portefeuille, $type);

        // Vérification des assertions
        $this->assertArrayHasKey('recherche', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('KO', $result['recherche']);  // Vérifie que la recherche est KO
        $this->assertEquals('Pas de contenu !!!', $result['content']);
    }

    public function testLogrotate()
    {
        // 1. Utilisez rtrim() pour nettoyer les barres obliques inversées en trop à la fin du chemin
        $fileToRotate = rtrim($this->fullAuditPath, '\\') . DIRECTORY_SEPARATOR . 'rotation_MA_MOULINETTE.log';
        file_put_contents($fileToRotate, str_repeat('x', 1048577)); // Fichier de 1 Mo et 1 octet

        // 2. Simuler la classe Rotation
        $rotationMock = $this->createMock(Rotation::class);
        $rotationMock->expects($this->any())
                      ->method('rotate')
                      ->with($fileToRotate);

        // 3. Simuler Filesystem
        $filesystemMock = $this->createMock(Filesystem::class);
        $filesystemMock->method('exists')->willReturn(true);  // Simuler l'existence du répertoire

        // 4. Simuler Finder
        $finderMock = $this->createMock(Finder::class);
        $finderMock->method('files')->willReturn($finderMock);
        $finderMock->method('in')->with($this->fullAuditPath)->willReturn($finderMock);
        $finderMock->method('depth')->with(0)->willReturn($finderMock);
        $finderMock->method('sortByName')->willReturn($finderMock);

        // Simuler un fichier trouvé par Finder
        $mockFile = $this->createMock(\Symfony\Component\Finder\SplFileInfo::class);
        $mockFile->method('getPathname')->willReturn($fileToRotate);
        $finderMock->method('getIterator')->willReturn(new \ArrayIterator([$mockFile]));  // Retourne un fichier

        // 5. Simuler ParameterBagInterface si nécessaire
        $paramsMock = $this->createMock(ParameterBagInterface::class);
        $paramsMock->method('get')->will($this->returnValueMap([
            ['kernel.project_dir', 'C:/environnement/ma-moulinette'], // Racine du projet
            ['path.audit', '/Tests/Fixtures'], // Répertoire de logs
        ]));

        // 6. Appliquer le test (Appel de la méthode logrotate)
        $this->fileLogger->logrotate();

        // Si la méthode passe sans erreur, tout va bien
        $this->assertTrue(true);
        $compressedFile = $fileToRotate . '.1' . '.gz';
        $this->assertFileExists($compressedFile);
        /** On supprime le fichier */
        unlink($compressedFile);
    }

    public function testFileWithSimpleArray(): void
    {
        // Préparer les données d'entrée
        $collecte = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $portfolio = 'portfolio1';

        // Générer le contenu HTML attendu via formatArray
        $expectedFormattedContent = '<p><strong>key1 : </strong> value1</p><p><strong>key2 : </strong> value2</p>';

        // Configurer l'attente sur la méthode log()
        $fileLoggerMock = $this->getMockBuilder(FileLogger::class)
            ->setConstructorArgs([$this->params, $this->filesystem, $this->finder, $this->rotation])
            ->onlyMethods(['log'])
            ->getMock();

        $fileLoggerMock->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($portfolio),                  // Vérifier que le portefeuille est correct
                $this->equalTo($expectedFormattedContent),   // Vérifier que le contenu est formaté correctement
                $this->equalTo('append')                    // Vérifier que l'action est bien "append"
            );

        // Appeler la méthode file
        $fileLoggerMock->file($portfolio, $collecte);
    }

    public function testFileWithNestedArray(): void
    {
        // Préparer les données d'entrée
        $collecte = [
            'key1' => 'value1',
            'nested' => [
                'subkey1' => 'subvalue1',
                'subkey2' => 'subvalue2',
            ],
        ];
        $portfolio = 'portfolio_nested';

        // Contenu attendu après formatage
        $expectedFormattedContent = '<p><strong>key1 : </strong> value1</p>'
            . '<h2>nested</h2>'
            . '<p><strong>subkey1 : </strong> subvalue1</p>'
            . '<p><strong>subkey2 : </strong> subvalue2</p>';

        // Simuler la méthode log()
        $fileLoggerMock = $this->getMockBuilder(FileLogger::class)
            ->setConstructorArgs([$this->params, $this->filesystem, $this->finder, $this->rotation])
            ->onlyMethods(['log'])
            ->getMock();

        $fileLoggerMock->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($portfolio),
                $this->equalTo($expectedFormattedContent),
                $this->equalTo('append')
            );

        // Appeler la méthode file
        $fileLoggerMock->file($portfolio, $collecte);
    }

    public function testFileWithSpecialValues(): void
    {
      // Préparer les données d'entrée
      $collecte = [
          'date' => new \DateTime('2024-01-01 12:00:00'),
          'object' => (object) ['property' => 'value'],
          'number' => 42,
      ];
      $portfolio = 'portfolio_special';

      // Contenu attendu après formatage
      $expectedFormattedContent = '<p><strong>date : </strong> 2024-01-01 12:00:00</p><p><strong>object : </strong> stdClass Object([property] =&gt; value)</p><p><strong>number : </strong> 42</p>';

      // Simuler la méthode log()
      $fileLoggerMock = $this->getMockBuilder(FileLogger::class)
          ->setConstructorArgs([$this->params, $this->filesystem, $this->finder, $this->rotation])
          ->onlyMethods(['log'])
          ->getMock();

      $fileLoggerMock->expects($this->once())
          ->method('log')
          ->with(
              $this->equalTo($portfolio),
              $this->equalTo($expectedFormattedContent),
              $this->equalTo('append')
          );

      // Appeler la méthode file
      $fileLoggerMock->file($portfolio, $collecte);
    }

    public function testFileWithEmptyArray(): void
    {
        // Préparer les données d'entrée
        $collecte = [];
        $portfolio = 'portfolio_empty';

        // Contenu attendu après formatage (chaîne vide)
        $expectedFormattedContent = '';

        // Simuler la méthode log()
        $fileLoggerMock = $this->getMockBuilder(FileLogger::class)
            ->setConstructorArgs([$this->params, $this->filesystem, $this->finder, $this->rotation])
            ->onlyMethods(['log'])
            ->getMock();

        $fileLoggerMock->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($portfolio),
                $this->equalTo($expectedFormattedContent),
                $this->equalTo('append')
            );

        // Appeler la méthode file
        $fileLoggerMock->file($portfolio, $collecte);
    }

    public function testFileWithNumericKeys(): void
    {
        // Préparer les données d'entrée
        $collecte = [
            0 => 'value1',
            1 => 'value2',
        ];
        $portfolio = 'portfolio_numeric_keys';

        // Contenu attendu après formatage (pas de clés, seulement les valeurs)
        $expectedFormattedContent = '<p>value1</p><p>value2</p>';

        // Simuler la méthode log()
        $fileLoggerMock = $this->getMockBuilder(FileLogger::class)
            ->setConstructorArgs([$this->params, $this->filesystem, $this->finder, $this->rotation])
            ->onlyMethods(['log'])
            ->getMock();

        $fileLoggerMock->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($portfolio),
                $this->equalTo($expectedFormattedContent),
                $this->equalTo('append')
            );

        // Appeler la méthode file
        $fileLoggerMock->file($portfolio, $collecte);
    }

    public function testFileWithSpecialCharacters(): void
    {
        // Préparer les données d'entrée
        $collecte = [
            'key1' => '<script>alert("xss")</script>',
        ];
        $portfolio = 'portfolio_special_chars';

        // Contenu attendu après échappement des caractères spéciaux
        $expectedFormattedContent = '<p><strong>key1 : </strong> &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</p>';

        // Simuler la méthode log()
        $fileLoggerMock = $this->getMockBuilder(FileLogger::class)
            ->setConstructorArgs([$this->params, $this->filesystem, $this->finder, $this->rotation])
            ->onlyMethods(['log'])
            ->getMock();

        $fileLoggerMock->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($portfolio),
                $this->equalTo($expectedFormattedContent),
                $this->equalTo('append')
            );

        // Appeler la méthode file
        $fileLoggerMock->file($portfolio, $collecte);
    }

    public function testLogWithAppendType(): void
    {
        /** @var \Symfony\Component\Filesystem\Filesystem|\PHPUnit\Framework\MockObject\MockObject */
        $filesystemMock = $this->createMock(Filesystem::class);
        $filesystemMock->method('exists')->willReturn(true); // Simuler que le répertoire existe
        $filesystemMock->expects($this->any())
            ->method('appendToFile')
            ->with($this->stringContains('manuel_test.log'), $this->stringContains('test log'), $this->stringContains('append'));
        $fileLogger = new FileLogger($this->params, $filesystemMock, $this->finder, $this->rotation);
        $result = $fileLogger->log('test', 'test log', 'append');

        $this->assertEquals(200, $result); // Vérifier que la méthode renvoie bien 200
    }

    public function testLogWithRemoveType(): void
    {
      // Simuler un mock pour Filesystem
      /** @var \Symfony\Component\Filesystem\Filesystem */
      $filesystemMock = $this->createMock(Filesystem::class);

        // Simuler le comportement de la méthode exists pour qu'elle retourne true
        $filesystemMock->method('exists')->willReturn(true);

        // Créer l'instance de FileLogger avec le mock
        $fileLogger = new FileLogger($this->params, $filesystemMock, $this->finder, $this->rotation);

        // Appeler la méthode log avec un type 'remove'
        $result = $fileLogger->log('portfolio_special', 'test log', 'remove');

        // Vérifier que la méthode retourne 202 pour un type non supporté
        $this->assertEquals(202, $result);
    }

    public function testLogWithUnsupportedType()
    {
         /** @var \Symfony\Component\Filesystem\Filesystem */
        $filesystemMock = $this->createMock(Filesystem::class);
        $filesystemMock->method('exists')->willReturn(true);

        $fileLogger = new FileLogger($this->params, $filesystemMock, $this->finder, $this->rotation);
        $result = $fileLogger->log('test', 'test log', 'unknownType');
        /** On supprime le fichier et on renvoie 200 */
        $this->assertEquals(400, $result);
    }

    public function testLogWithNonExistingPath(): void
    {
        // 5. Simuler ParameterBagInterface si nécessaire
        /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
        $paramsMock = $this->createMock(ParameterBagInterface::class);
        $paramsMock->method('get')->will($this->returnValueMap([
            ['kernel.project_dir', 'c:/some-folder/ma-moulinette'], // Racine du projet
            ['path.audit', '/var/audit'], // Répertoire de logs
        ]));

        // Injectez ce mock dans votre service FileLogger
        $fileLogger = new FileLogger($paramsMock, $this->filesystem, $this->finder, $this->rotation);

        // Ensuite vous pouvez tester comme avant
        $result = $fileLogger->log('portfolio_special', 'test log', 'append');
        $this->assertEquals(404, $result); // Le chemin n'existe pas dans ce cas
    }

    public function testLogWithRemove(): void
    {
      $fileToRotate = rtrim($this->fullAuditPath, '\\') . DIRECTORY_SEPARATOR . 'manuel_remove.log';
      file_put_contents($fileToRotate, str_repeat('x', 1));

      /** @var \Symfony\Component\Filesystem\Filesystem */
      $filesystemMock = $this->createMock(Filesystem::class);
      $filesystemMock->method('exists')->willReturn(true);

      // 3. Définir le comportement attendu du mock
      $filesystemMock->expects($this->any())
          ->method('remove')
          ->with($this->stringContains('manuel_remove.log'));

      $fileLogger = new FileLogger($this->params, $filesystemMock, $this->finder, $this->rotation);
      $result = $fileLogger->log('test', 'test log', 'remove');

      $this->assertEquals(202, $result);
    }

    protected function tearDown(): void
    {
        $filePath = $this->fullAuditPath . DIRECTORY_SEPARATOR .'manuel_test.log';
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        parent::tearDown();
    }
}
