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

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use App\Entity\Main\Batch;
use Doctrine\ORM\EntityManagerInterface;

class ContainsBatchUniqueValidator extends ConstraintValidator
{
    /**
     * [Description for __construct]
     * EntityManagerInterface $em
     *
     * @param  private
     *
     * Created at: 13/02/2023, 09:22:44 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * [Description for validate]
     * Création d'une violation sur les valeurs Uniques
     *
     * @param mixed $value
     * @param Constraint $constraint
     *
     * @return void
     *
     * Created at: 13/02/2023, 09:23:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function validate($value, Constraint $constraint): void
    {
        /** La contrainte s'applique uniquement à la class équipe */
        if (!$constraint instanceof ContainsBatchUnique) {
            throw new UnexpectedTypeException($constraint, ContainsBatchUnique::class);
        }

        /** La valeur ne doit pas $etre null ou vide. */
        if (null === $value || '' === $value) {
            return;
        }

        /** La valeur doit être de type string. */
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        /** On cherche si la valeur exsite déjà.  renvoi null ou un objet*/
        $portefeuille = $this->em->getRepository(Batch::class)->findOneBy(['portefeuille' => mb_strtoupper($value)]);

        /** Si la valeur existe, on affiche une erreur. */
        if ($portefeuille) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }

        /** On cherche si la valeur exsite déjà.  renvoi null ou un objet*/
        $titre = $this->em->getRepository(Batch::class)->findOneBy(['titre' => mb_strtoupper($value)]);

        /** Si la valeur existe, on affiche une erreur. */
        if ($titre) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
