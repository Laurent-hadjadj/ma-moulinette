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

use App\Entity\Main\Utilisateur;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => true,
                'attr' => [
                            'maxlength' => 32,
                            'placeholder' => 'placeholder.nom',
                            'class' => 'color-bleu',
                            'autocomplete' => 'family-name'
                        ],
                'label' => 'label.nom',
                'trim' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre nom.',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom doit comporter au moins de {{ limit }} caractères.',
                        'max' => 32,
                        'maxMessage' => 'Le nom ne doit pas comporter plus de {{ limit }} caractères.',
                    ]), ]
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'label' => 'label.prenom',
                'trim' => true,
                'attr' => [ 'maxlength' => 32,
                            'placeholder' => 'placeholder.prenom',
                            'class' => 'color-bleu',
                            'autocomplete' => 'given-name' ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre prénom.',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'max' => 32,
                        'maxMessage' => 'Le prénom ne doit pas comporter plus de {{ limit }} caractères.',
                    ]), ]
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'steeve@gmail.com',
                    'tabindex' => '-1',
                    'autocomplete' => 'off',
                    'class' => 'email']])

            //RFC 3696 (64+1+255).
            ->add('courriel', EmailType::class, [
                'label' => 'label.courriel',
                'trim' => true,
                'attr' => [
                    'maxlength' => 320,
                    'placeholder' => 'placeholder.courriel',
                    'class' => 'color-bleu',
                    'autocomplete' => 'off'
                ],
            ])

            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options'  => [
                    'always_empty' => true,
                    'hash_property_path' => 'password',
                    'label' => 'label.motdepasse',
                    'attr' => [
                        'placeholder' => 'placeholder.motdepasse',
                        'class' => 'color-bleu',
                        'autocomplete' => 'off',
                        'aria-label'=>'Mot de passe',
                        'aria-describedby'=>'registration_form_plainPassword_first'],
                ],
                'second_options' => [
                    'label' => 'label.remotdepasse',
                    'attr' => [
                        'placeholder' => 'placeholder.remotdepasse',
                        'class' => 'color-bleu',
                        'autocomplete' => 'off',
                        'aria-label'=>'Vérification mot de passe',
                        'aria-describedby'=>'registration_form_plainPassword_second' ],
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
            ])
            ->add('avatar', HiddenType::class, [
                'data' => 'personne.png',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
