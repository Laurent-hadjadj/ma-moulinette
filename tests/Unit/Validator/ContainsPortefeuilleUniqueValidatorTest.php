<?php

namespace App\Tests\Validator;

use App\Validator\ContainsPortefeuilleUniqueValidator;
use App\Validator\ContainsPortefeuilleUnique;
use App\Entity\Portefeuille;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class ContainsPortefeuilleUniqueValidatorTest extends TestCase
{
    private $validator;
    /** @var EntityManagerInterface */
    private $entityManagerMock;
    /** @var ExecutionContextInterface */
    private $contextMock;
    private $violationBuilderMock;

    protected function setUp(): void
    {
        // Mock de l'EntityManager
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // Mock de l'ExecutionContext
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);

        // Mock de ConstraintViolationBuilder
        $this->violationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->violationBuilderMock
            ->method('addViolation')
            ->willReturnSelf();

        $this->contextMock
            ->method('buildViolation')
            ->willReturn($this->violationBuilderMock);

        // Instanciation du validator
        $this->validator = new ContainsPortefeuilleUniqueValidator($this->entityManagerMock);
        $this->validator->initialize($this->contextMock);
    }

    public function testValidateWithExistingValue(): void
    {
        // Simule un portefeuille existant
        $portefeuille = new Portefeuille();
        $portefeuille->setTitre('EXISTING_TITLE');

        // Mock du repository
        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock
            ->method('findOneBy')
            ->with(['titre' => 'EXISTING_TITLE'])
            ->willReturn($portefeuille);

        $this->entityManagerMock
            ->method('getRepository')
            ->with(Portefeuille::class)
            ->willReturn($repositoryMock);

        // Création de la contrainte
        $constraint = new ContainsPortefeuilleUnique();

        // Définit les attentes pour buildViolation
        $this->contextMock
            ->expects($this->once())
            ->method('buildViolation')
            ->with('[Portefeuille] La valeur "{{ string }}" existe déjà.')
            ->willReturn($this->violationBuilderMock);

        // Définit les attentes pour setParameter
        $this->violationBuilderMock
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ string }}', 'EXISTING_TITLE')
            ->willReturnSelf();

        // Définit les attentes pour addViolation
        $this->violationBuilderMock
            ->expects($this->once())
            ->method('addViolation');

        // Exécution de la méthode validate
        $this->validator->validate('EXISTING_TITLE', $constraint);
    }

    public function testValidateWithNewValue(): void
    {
        // Mock du repository qui ne trouve aucun portefeuille
        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManagerMock
            ->method('getRepository')
            ->with(Portefeuille::class)
            ->willReturn($repositoryMock);

        $constraint = new ContainsPortefeuilleUnique();

        // Aucune violation ne doit être ajoutée
        $this->contextMock
            ->expects($this->never())
            ->method('buildViolation');

        // Exécution de la méthode validate
        $this->validator->validate('NEW_TITLE', $constraint);
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new ContainsPortefeuilleUnique();

        // Aucune violation ne doit être ajoutée pour une valeur nulle
        $this->contextMock
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    public function testValidateWithNonStringValue(): void
    {
        $constraint = new ContainsPortefeuilleUnique();

        // Simule une exception pour une valeur non chaîne
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedValueException::class);

        $this->validator->validate(123, $constraint);
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
}
