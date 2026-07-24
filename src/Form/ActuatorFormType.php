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
use Symfony\Component\Validator\Constraints\{Length, NotBlank, Url};
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
            ->add('nomApplication', TextType::class, [
                    'required' => true,
                    'label' => 'label.actuator.nom_application',
                    'label_attr' => [
                        'class' => 'color-bleu open-sans font-size-08',
                    ],
                    'trim' => true,
                    'attr' => [ 'placeholder' => 'placeholder.actuator.nom_application',
                                'class' => 'color-bleu' ],
                    'constraints' => [
                        new NotBlank(message: "Entrez le nom de l'application."),
                        new Length(max: 128, maxMessage: "Le nom de l'application ne doit pas dépasser {{ limit }} caractères."),
                    ],
            ])
            ->add('mavenKey', TextType::class, [
                    'required' => true,
                    'label' => 'label.actuator.maven_key',
                    'label_attr' => [
                        'class' => 'color-bleu open-sans font-size-08',
                    ],
                    'trim' => true,
                    'attr' => [ 'placeholder' => 'placeholder.actuator.maven_key',
                                'class' => 'color-bleu' ],
                    'constraints' => [
                        new NotBlank(message: "Entrez la clé Maven du projet."),
                        new Length(max: 255, maxMessage: "La clé Maven ne doit pas dépasser {{ limit }} caractères."),
                    ],
            ])
            ->add('personne', TextType::class, [
                    'required' => true,
                    'label' => 'label.actuator.personne',
                    'label_attr' => [
                        'class' => 'color-bleu open-sans font-size-08',
                    ],
                    'trim' => true,
                    'attr' => [ 'placeholder' => 'placeholder.actuator.personne',
                                'class' => 'color-bleu' ],
                    'constraints' => [
                        new NotBlank(message: "Entrez le prénom et nom du responsable."),
                        new Length(max: 128, maxMessage: "Le prénom et nom du responsable ne doit pas dépasser {{ limit }} caractères."),
                    ],
            ])
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
                    ),
                    /* MODIF 2026-07-23 : sans cette contrainte, une URL sans schéma
                     * (ex. "localhost:8081/app") passait la validation et ne plantait
                     * qu'au moment du ping (exception non catchée, erreur 500) — bug
                     * réel trouvé en test manuel. */
                    new Url(
                        message: "Entrez une URL valide, avec http:// ou https:// (ex. http://localhost:8081/monapplication).",
                        protocols: ['http', 'https'],
                        // Actuator cible en priorité des environnements de dev/test,
                        // très souvent sur "localhost" (sans domaine de premier niveau).
                        requireTld: false,
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
                    'required' => $options['mode'] === 'ajouter',
                    'label' => 'label.actuator.password',
                    'label_attr' => [
                        'class' => 'color-bleu open-sans font-size-08 margin-left-05',
                    ],
                    'attr' => [ 'placeholder' => 'placeholder.actuator.password',
                                'class' => 'color-bleu margin-left-05'],
                    /* MODIF 2026-07-23 : mot de passe obligatoire uniquement à la création.
                     * En modification, vide = conserver le mot de passe déjà enregistré
                     * (ActuatorController::actuatorModifier ne préremplit jamais ce champ). */
                    'constraints' => $options['mode'] === 'ajouter'
                        ? [ new NotBlank(message: "Entrez le mot de passe du compte Actuator.") ]
                        : [],
            ])
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
            'mode' => 'ajouter',
        ]);
        $resolver->setAllowedValues('mode', ['ajouter', 'modifier']);
    }
}
