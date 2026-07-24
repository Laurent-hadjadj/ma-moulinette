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

namespace App\Form;

use App\Entity\ActuatorInfo;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Validator\Constraints\{Length, NotBlank};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @extends AbstractType<ActuatorInfo>
 */
class ActuatorInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('actuatorInfoCle', TextType::class,
            [
                'required' => true,
                'label' => 'label.actuator.info.cle',
                'label_attr' => [
                    'class' => 'color-bleu open-sans font-size-08 margin-left-05',
                ],
                'trim' => true,
                'attr' => [ 'placeholder' => 'placeholder.actuator.info.cle',
                            'class' => 'color-bleu open-sans margin-left-05' ],
                'constraints' => [
                    new NotBlank(message : "Entrez le nom de la clé (nœud JSON)."),
                    new Length(
                        min: 2,
                        max: 255,
                        minMessage: "Le nom de la clé doit comporter au moins {{ limit }} caractères.",
                        maxMessage: "Le nom de la clé ne doit pas comporter plus de {{ limit }} caractères.",
                    ), ],
            ])
            ->add('actuatorInfoDescription', TextType::class,
            [
                'required' => true,
                'label' => 'label.actuator.info.description',
                'label_attr' => [
                    'class' => 'color-bleu open-sans font-size-08',
                ],
                'trim' => true,
                'attr' => [ 'placeholder' => 'placeholder.actuator.info.description',
                            'class' => 'color-bleu open-sans' ],
                'constraints' => [
                    new NotBlank(message:  "Entrez une description courte."),
                    new Length(
                        min: 1,
                        max: 255,
                        minMessage: "La description doit comporter au moins {{ limit }} caractère.",
                        maxMessage: "La description ne doit pas comporter plus de {{ limit }} caractères.",
                    ), ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActuatorInfo::class,
        ]);
    }
}
