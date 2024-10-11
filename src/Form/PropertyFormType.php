<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Форма для проверки обновления seo
 *
 * @package App\Form
 */
class PropertyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите название',
                    ]),
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Имя не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ])
            ->add('content', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите content текст',

                    ]),
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Content не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
