<?php

namespace App\Form;

use App\Entity\ActuatorInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ActuatorInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('actuatorInfoValue', TextType::class,
            [
                'required' => true,
                'label' => 'label.actuator.info.value',
                'label_attr' => [
                    'class' => 'color-bleu open-sans font-size-08 margin-left-05',
                ],
                'trim' => true,
                'attr' => [ 'placeholder' => 'placeholder.actuator.info.value',
                            'class' => 'color-bleu open-sans margin-left-05' ],
                'constraints' => [
                    new NotBlank([
                        'message' => "Entrez la valeur de la clé.",
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => "La valeur de la clé doit comporter au moins {{ limit }} caractères.",
                        'max' => 255,
                        'maxMessage' => "La valeur de la clé ne doit pas comporter plus de {{ limit }} caractères.",
                    ]), ],
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
                    new NotBlank([
                        'message' => "Entrez une description courte.",
                    ]),
                    new Length([
                        'min' => 1,
                        'minMessage' => "La description doit comporter au moins {{ limit }} caractère.",
                        'max' => 255,
                        'maxMessage' => "La description ne doit pas comporter plus de {{ limit }} caractères.",
                    ]), ],
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
