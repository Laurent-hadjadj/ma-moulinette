<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\UserAgentEventRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-09 : tests Integration handleDatabaseException
 * UserAgentEventRepository — utilise les vraies classes DBAL (pas de mocks).
 */
class UserAgentEventRepositoryHandleDatabaseExceptionTest extends TestCase
{
    private UserAgentEventRepository $repo;

    protected function setUp(): void
    {
        /** @var ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject */
        $registry   = $this->createStub(ManagerRegistry::class);
        $this->repo = new UserAgentEventRepository($registry);
    }

    public function testHandleDatabaseException_Generic(): void
    {
        $ex  = new \RuntimeException('Une erreur inattendue');
        $res = $this->repo->handleDatabaseException($ex);
        $this->assertSame(['code' => 500, 'erreur' => 'Une erreur inattendue'], $res);
    }

    public function testHandleDatabaseException_NoDatabase(): void
    {
        /** @var \Doctrine\DBAL\Driver\Exception&\PHPUnit\Framework\MockObject\MockObject */
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $ex       = new ConnectionException($driverEx, null);

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame([
            'code'   => 500,
            'erreur' => UserAgentEventRepository::$noDataBase,
        ], $res);
    }

    public function testHandleDatabaseException_NotNullViolation(): void
    {
        /** @var \Doctrine\DBAL\Driver\Exception&\PHPUnit\Framework\MockObject\MockObject */
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $ex       = new NotNullConstraintViolationException($driverEx, null);

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame(500, $res['code']);
        // Le message est celui de la DBAL exception (chaîne vide si driver stub)
        $this->assertArrayHasKey('erreur', $res);
    }

    public function testHandleDatabaseException_UniqueViolation(): void
    {
        /** @var \Doctrine\DBAL\Driver\Exception&\PHPUnit\Framework\MockObject\MockObject */
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $ex       = new UniqueConstraintViolationException($driverEx, null);

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame([
            'code'   => 23505,
            'erreur' => 'Les informations existent déjà.',
        ], $res);
    }
}
