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

namespace App\Tests\Validator;

use App\Entity\Batch;
use App\Validator\ContainsBatchUnique;
use App\Validator\ContainsBatchUniqueValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * [Description ContainsBatchUniqueValidatorTest]
 */
class ContainsBatchUniqueValidatorTest extends TestCase
{
    private $validator;
    /** @var EntityManagerInterface */
    private $entityManagerMock;
    /** @var ExecutionContextInterface */
    private $contextMock;
    private $constraintMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock de l'EntityManager
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // Mock du contexte d'exécution
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);

        // Mock de la contrainte
        $this->constraintMock = $this->createMock(ContainsBatchUnique::class);
        $this->constraintMock->message = '[Traitement] La valeur "{{ string }}" existe déjà.';

        // Création du validateur
        $this->validator = new ContainsBatchUniqueValidator($this->entityManagerMock);
        $this->validator->initialize($this->contextMock);
    }

    public function testValidateWithValidValue(): void
    {
        $value = 'BatchTest';

        // Simulation du cas où aucune équipe avec ce titre n'existe
        $this->entityManagerMock
            ->method('getRepository')
            ->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));

        // Aucune violation, donc le validateur ne doit rien faire
        $this->contextMock
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $this->constraintMock);
    }

    public function testValidateWithExistingValue(): void
    {
        $value = 'BatchTest';

        // Simulation du cas où un batch avec ce titre existe déjà
        $mockBatch = $this->createMock(Batch::class);
        $this->entityManagerMock
            ->method('getRepository')
            ->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));
        $this->entityManagerMock->getRepository(Batch::class)
            ->method('findOneBy')
            ->willReturn($mockBatch);

        // Simulation de la création de la violation
        $violationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilderMock
            ->expects($this->once())  // On attend que setParameter soit appelé une fois
            ->method('setParameter')
            ->with('{{ string }}', $value)
            ->willReturnSelf();
        $violationBuilderMock
            ->expects($this->once())  // On attend que addViolation soit appelé une fois
            ->method('addViolation');

        // On s'attend à ce qu'une violation soit construite avec le message de la contrainte
        $this->contextMock
            ->expects($this->once())  // On attend que buildViolation soit appelé une fois
            ->method('buildViolation')
            ->with($this->constraintMock->message)
            ->willReturn($violationBuilderMock);

        // Appel à la méthode validate
        $this->validator->validate($value, $this->constraintMock);
    }


    public function testValidateWithUnexpectedConstraint(): void
    {
        // On s'attend à une exception de type TypeError
        $this->expectException(\TypeError::class);

        // Passer une contrainte invalide
        $this->validator->validate('BatchTest', new \stdClass());
    }

    public function testValidateWithUnexpectedConstraintType(): void
    {
        // Crée une instance d'une classe quelconque pour simuler un type incorrect
        $invalidConstraint = $this->createMock(\stdClass::class);  // Utilisation de stdClass comme contrainte incorrecte

        // Exécute la validation avec l'instance de la contrainte incorrecte
        $this->expectException(\TypeError::class); // On attend une TypeError

        // Appel à la méthode validate avec le mauvais type de contrainte
        $this->validator->validate('EXISTING_TITLE', $invalidConstraint);
    }

    public function testValidateWithInvalidValueType(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedValueException::class);

        // On passe une valeur de type non-string
        $this->validator->validate(123, $this->constraintMock);
    }

}
