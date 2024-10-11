<?php

namespace App\Form\Tracker;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Tracker;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Форма для создания задачи в трекер
 *
 * @package App\Form
 */
class TrackerCreateType extends AbstractType
{
    private UserRepository $userRepository;
    private CoreSecurity $security;

    public function __construct(
        UserRepository $userRepository,
        CoreSecurity $security
    ) {
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tracker_id', EntityType::class, [
                "class" => Tracker::class,
                'invalid_message' => 'Задача не найдена',
            ])
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите название задачи',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите название задачи',
                    ]),
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => "Название должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "Название не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                ],
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
