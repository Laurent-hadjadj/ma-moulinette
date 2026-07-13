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

use App\Entity\Actuator;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Validator\Constraints\{Length, NotBlank};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{UrlType, TextType, CollectionType};

/**
 * [Description ActuatorFormType]
 *
 * @extends AbstractType<Actuator>
 */
class ActuatorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', UrlType::class, [
                'required' => true,
                'label' => 'label.actuator.url',
                'label_attr' => [
                    'class' => 'color-bleu open-sans font-size-08',
                ],
                'trim' => true,
                'compound' => false,
                'attr' => [ 'placeholder' => 'placeholder.actuator.url',
                            'class' => 'color-bleu' ],
                'constraints' => [
                    new NotBlank(message : "Entrez l'adresse du site web."),
                    new Length(
                        min: 12,
                        max: 128,
                        minMessage: "L'URL doit comporter au moins {{ limit }} caractères.",
                        maxMessage: "L'URL ne doit pas comporter plus de {{ limit }} caractères.",
                    ), ],
            ])
            ->add('actuatorUser', TextType::class, [
                    'label' => 'label.actuator.user',
                    'label_attr' => [
                        'class' => 'color-bleu open-sans font-size-08',
                    ],
                    'trim' => true,
                    'attr' => [ 'placeholder' => 'placeholder.actuator.user',
                                'class' => 'color-bleu open-sans' ]])
            ->add('actuatorPassword',  TextType::class, [
                    'label' => 'label.actuator.password',
                    'label_attr' => [
                        'class' => 'color-bleu open-sans font-size-08 margin-left-05',
                    ],
                    'attr' => [ 'placeholder' => 'placeholder.actuator.password',
                                'class' => 'color-bleu margin-left-05']])
            ->add('actuatorInfo', CollectionType::class, [
                    'entry_type' => ActuatorInfoFormType::class,
                    'by_reference' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'error_bubbling' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Actuator::class,
        ]);
    }
}
