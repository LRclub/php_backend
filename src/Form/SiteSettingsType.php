<?php

namespace App\Form;

use App\Entity\SiteSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class SiteSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('setting_id', IntegerType::class)
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Название не должно быть длиннее {{ limit }} символов",
                        ]
                    )
                ],
            ])
            ->add('code', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Код не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                    new Callback([$this, 'validateCode'])
                ],
            ])
            ->add('value', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Значение не должно быть длиннее {{ limit }} символов",
                        ]
                    )
                ],
            ]);
    }

    public function validateCode($value, ExecutionContextInterface $context)
    {
        if (
            !preg_match("#^[aA-zZ0-9_]+$#", $value) ||
            ctype_digit($value)
        ) {
            return $context
                ->buildViolation('Код должен содержать латинские буквы, цифры (необязательно) и нижнее подчеркивание!')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteSettings::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
