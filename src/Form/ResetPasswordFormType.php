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

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Utilisateur;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('ancienMotDePasse', PasswordType::class, [
            'hash_property_path' => 'password',
            'mapped' => false,
            'always_empty' => true,
            'label' => 'label.ancien.motdepasse',
            'attr' => [
                'placeholder' => 'placeholder.ancien.motdepasse',
                'class' => 'color-bleu',
                'autocomplete' => 'off',
                'aria-label'=>'Ancien mot de passe',
                'aria-describedby'=>'reset_password_form_ancienMotDePasse',
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Merci de saisir votre mot de passe actuel.',
                ]),
            ]
        ])
        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'invalid_message' => 'invalid.message.motdepasse',
            'first_options' => [
                'always_empty' => true,
                'hash_property_path' => 'password',
                'label' => 'label.nouveau.motdepasse',
                'attr' => [
                    'placeholder' => 'placeholder.nouveau.motdepasse',
                    'class' => 'color-bleu margin-bottom-05',
                    'autocomplete' => 'off',
                    'aria-label'=>'Nouveau mot de passe',
                    'aria-describedby'=>'reset_password_form_plainPassword_first'],
            ],
            'second_options' => [
                'label' => 'label.remotdepasse',
                'attr' => [
                    'placeholder' => 'placeholder.remotdepasse',
                    'class' => 'color-bleu',
                    'autocomplete' => 'off',
                    'aria-label'=>'Vérification mot de passe',
                    'aria-describedby'=>'reset_password_form_plainPassword_second' ],
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Merci de saisir votre mot de passe.',
                ]),
                new Length([
                    'min' => 8,
                    'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
                    'max' => 52,
                    'maxMessage' => 'Votre mot de passe ne doit pas comporter plus de {{ limit }} caractères.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }

}
