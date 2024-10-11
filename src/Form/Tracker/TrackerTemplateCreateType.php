<?php

namespace App\Form\Tracker;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\TrackerTemplate;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Форма для создания стандартных задач в трекер
 *
 * @package App\Form
 */
class TrackerTemplateCreateType extends AbstractType
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
            ->add('template_id', EntityType::class, [
                "class" => TrackerTemplate::class,
                'invalid_message' => 'Задача не найдена',
                'constraints' => [
                    new Callback([$this, 'validateTemplate']),
                ]
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

    /**
     * @param mixed $template
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateTemplate($template, ExecutionContextInterface $context)
    {
        if ($template) {
            if ($template->getIsDeleted()) {
                return $context
                    ->buildViolation('Задача не найдена')
                    ->addViolation();
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
