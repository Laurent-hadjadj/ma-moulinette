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
            ->add('nom',TextType::class, [
                'required' => true,
                'attr' => ['maxlength' => 64],
                'attr' => [ 'placeholder' => 'placeholder.nom',
                            'style'=>'color:#00445b;',
                            'autocomplete'=> 'family-name'
                        ],
                'label' => 'label.nom',
                'trim' => true
            ])
            ->add('prenom',TextType::class, [
                'required' => true,
                'attr' => ['maxlength' => 32],
                'attr' => ['placeholder' => 'placeholder.prenom',
                'style'=>'color:#00445b;',
                'autocomplete'=> 'given-name'
            ],
                'label' => 'label.prenom',
                'trim' => true
            ])
            //RFC 3696 (64+1+255).
            ->add('courriel',EmailType::class, [
                'attr' => ['maxlength' => 320],
                'attr' => ['placeholder' => 'placeholder.courriel',
                'style'=>'color:#00445b;',
                'autocomplete'=> 'email'
            ],
                'label' => 'label.courriel',
                'trim' => true
                //There is already an account with this courriel
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [ 'style'=>'color:#00445b;',
                            'autocomplete' => 'new-password'],
                'label' => 'label.motdepasse',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre mot de passe.',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit avoir au moins {{ limit }} caratères.',
                        'max' => 52,
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
