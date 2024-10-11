<?php

namespace App\Form\Tasks;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Tasks;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Форма для создания задачи
 *
 * @package App\Form
 */
class CreateType extends AbstractType
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
            ->add('task_id', EntityType::class, [
                "class" => Tasks::class,
                'invalid_message' => 'Задача не найдена',
                'constraints' => [
                    new Callback([$this, 'validateTask'])
                ],
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
            ])
            ->add('task_time', DateType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                /*'constraints' => [
                    new Callback([$this, 'validateTaskTime'])
                ],*/
            ));
    }

    /**
     * @param mixed $task
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateTask($task, ExecutionContextInterface $context)
    {
        if ($task) {
            if ($task->getUser()->getId() != $this->security->getUser()->getId()) {
                return $context
                    ->buildViolation('Вы не можете редактировать эту задачу!')
                    ->addViolation();
            }
            if ($task->getIsDeleted()) {
                return $context
                    ->buildViolation('Задача удалена!')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $task_time
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateTaskTime($task_time, ExecutionContextInterface $context)
    {
        if ($task_time) {
            if ($task_time->getTimestamp() + 24 * 60 * 60 < time()) {
                return $context
                    ->buildViolation('Нельзя устанавливать прошедшую дату!')
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
