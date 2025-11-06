<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Tests\Unit\Validator;

use App\Entity\Groupe;
use App\Validator\ContainsGroupeUnique;
use App\Validator\ContainsGroupeUniqueValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * [Description ContainsGroupeUniqueValidatorTest]
 */
class ContainsGroupeUniqueValidatorTest extends TestCase
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
        $this->constraintMock = $this->createMock(ContainsGroupeUnique::class);
        $this->constraintMock->message = '[Équipe] La valeur "{{ string }}" existe déjà.'; // Message de la contrainte

        // Création du validateur
        $this->validator = new ContainsGroupeUniqueValidator($this->entityManagerMock);
        $this->validator->initialize($this->contextMock); // Initialiser le validateur avec le contexte
    }

    public function testValidateWithValidValue(): void
    {
        $value = 'GroupeTest';

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
        $value = 'GroupeTest';

        // Simulation du cas où une équipe avec ce titre existe déjà
        $mockGroupe = $this->createMock(Groupe::class);
        $this->entityManagerMock
            ->method('getRepository')
            ->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));
        $this->entityManagerMock->getRepository(Groupe::class)
            ->method('findOneBy')
            ->willReturn($mockGroupe);

        // S'attend à ce qu'une violation soit ajoutée
        $violationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilderMock
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ string }}', $value)
            ->willReturnSelf();
        $violationBuilderMock
            ->expects($this->once())
            ->method('addViolation');

        $this->contextMock
            ->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraintMock->message)
            ->willReturn($violationBuilderMock);

        $this->validator->validate($value, $this->constraintMock);
    }

    public function testValidateWithUnexpectedConstraint(): void
    {
        // On s'attend à une exception de type TypeError (si UnexpectedTypeException ne fonctionne pas)
        $this->expectException(\TypeError::class);

        // Passer une contrainte invalide
        $this->validator->validate('GroupeTest', new \stdClass());

        // Appel à la méthode validate avec le mauvais type de contrainte
        //$this->validator->validate('EXISTING_TITLE', $invalidConstraint);
    }

    public function testValidateWithUnexpectedConstraintType(): void
    {
        // Crée une instance d'une classe quelconque pour simuler un type incorrect
        $invalidConstraint = $this->createMock(Constraint::class);

        // Exécute la validation avec l'instance de la contrainte incorrecte
        $this->expectException(UnexpectedTypeException::class);

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
