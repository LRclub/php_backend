<?php

namespace App\Form\Payment;

use App\Entity\Files;
use App\Entity\Specialists;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\FormServices;
use App\Services\SMSServices;

/**
 * Форма для создания оплаты консультации
 *
 * @package App\Form
 */
class SpecialistPaymentType extends AbstractType
{
    private UserRepository $userRepository;
    private CoreSecurity $security;
    private FormServices $formServices;
    private SMSServices $SMSServices;

    public function __construct(
        UserRepository $userRepository,
        CoreSecurity $security,
        FormServices $formServices,
        SMSServices $SMSServices
    ) {
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->formServices = $formServices;
        $this->SMSServices = $SMSServices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('specialist', EntityType::class, [
                'class' => Specialists::class,
                'invalid_message' => 'ID специалиста не найдено',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите id специалиста',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите id специалиста',
                    ]),
                    new Callback([$this, 'validateSpecialist'])
                ],
            ])
            ->add('fio', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите ФИО',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите ФИО',
                    ]),
                    new Assert\Length(
                        [
                            'min' => 5,
                            'max' => 255,
                            'minMessage' => "ФИО должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "ФИО не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                    new Callback([$this->formServices, 'validateFIO'])
                ],
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Нужно указать номер телефона'
                    ]),
                    new Callback([$this, 'validatePhone'])
                ],
            ])
            ->add('email', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Email не указан'
                    ]),
                    new Assert\NotNull([
                        'message' => 'Email не указан'
                    ]),
                    new Callback([$this, 'validateEmail'])
                ],
            ])
            ->add('comment', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 1000,
                            'maxMessage' => "Комментарий не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                ]
            ]);
    }

    /**
     * @param mixed $phone
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validatePhone($phone, ExecutionContextInterface $context)
    {
        if ($phone) {
            $formatted_phone = $this->SMSServices->phoneFormat($phone);
            if (!$formatted_phone) {
                return $context
                    ->buildViolation('Введите корректный номер!')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $value
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateEmail($email, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        if (!empty($email)) {
            if ($this->userRepository->checkUniqueEmail($user->getId(), $email)) {
                return $context
                    ->buildViolation('Данный e-mail уже используется другим пользователем')
                    ->addViolation();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $context
                    ->buildViolation('Данный e-mail не может быть указан')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $specialist
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateSpecialist($specialist, ExecutionContextInterface $context)
    {
        if ($specialist && $specialist->getIsDeleted()) {
            return $context
                ->buildViolation('Специалист удален!')
                ->addViolation();
        }

        if ($specialist && !$specialist->getIsActive()) {
            return $context
                ->buildViolation('Специалист не активен!')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
