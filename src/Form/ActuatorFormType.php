<?php

namespace App\Form;

use App\Entity\Actuator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * [Description ActuatorFormType]
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
                    new NotBlank([
                        'message' => "Entrez l'adresse du site web.",
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => "L'URL doit comporter au moins {{ limit }} caractères.",
                        'max' => 128,
                        'maxMessage' => "L'URL ne doit pas comporter plus de {{ limit }} caractères.",
                    ]), ],
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
