<?php

namespace App\Form;

use App\Entity\Seo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\PropertyFormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SeoType extends AbstractType
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('seo_id', IntegerType::class)
            ->add('link', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Ссылка не должна быть длиннее {{ limit }} символов",
                        ]
                    ),
                    new Callback([$this, 'validateLink'])
                ],
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Заголовок не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 1000,
                            'maxMessage' => "Описание не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ])
            ->add('keywords', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 1000,
                            'maxMessage' => "Ключевые слова не должны быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ])
            ->add('property', CollectionType::class, [
                'entry_type' => PropertyFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true
            ])
            ->add('title_page', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Заголовок страницы не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ]);
    }

    public function validateLink($link, ExecutionContextInterface $context)
    {
        if ($link[0] != "/") {
            return $context
                ->buildViolation('Ссылка должна начинаться с /')
                ->addViolation();
        }
        $link = $this->params->get('base.url') . $link;

        // Remove all illegal characters from a url
        $url = filter_var($link, FILTER_SANITIZE_URL);

        // Validate url
        if (!filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $context
                ->buildViolation('Нужно указать правильную ссылку')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seo::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
