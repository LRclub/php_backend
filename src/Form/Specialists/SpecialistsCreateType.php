<?php

namespace App\Form\Specialists;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use App\Form\FormServices;
use App\Entity\Files;
use App\Entity\Specialists;
use App\Services\File\FileServices;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class SpecialistsCreateType extends AbstractType
{
    private ParameterBagInterface $params;
    private FormServices $formServices;

    public function __construct(
        ParameterBagInterface $params,
        FormServices $formServices
    ) {
        $this->params = $params;
        $this->formServices = $formServices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('specialist', EntityType::class, [
                'class' => Specialists::class,
                'invalid_message' => 'Специалист не найден',
                'constraints' => [
                    new Callback([$this, 'validateSpecialist'])
                ]
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
            ->add('speciality', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите специальность',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите специальность',
                    ]),
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => "Специальность должна содержать минимум {{ limit }} символа",
                            'maxMessage' => "Специальность не должна быть длиннее {{ limit }} символов",
                        ]
                    ),
                ],
            ])
            ->add('experience', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите опыт специалиста',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите опыт специалиста',
                    ]),
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => "Опыт должнен содержать минимум {{ limit }} символа",
                            'maxMessage' => "Опыт не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                ],
            ])
            ->add('price', MoneyType::class, [
                //'required' => true,
                'constraints' => [
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Нужно указать цену',
                    ]),
                    new Assert\NotBlank([
                        'message' => 'Нужно указать цену',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Нужно указать цену'
                    ]),
                ]
            ])
            ->add('sort', IntegerType::class, [])
            ->add('is_active', IntegerType::class, [])
            ->add('avatar', EntityType::class, [
                'class' => Files::class,
                'invalid_message' => 'Файл не найден',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Нужно добавить изображение',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Нужно добавить изображение'
                    ]),
                    new Callback([$this, 'validateAvatar'])
                ],
            ]);
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
    }

    /**
     * @param mixed $avatar
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateAvatar($avatar, ExecutionContextInterface $context)
    {
        if ($avatar) {
            $user = $avatar->getUser();
            if ($avatar->getFileType() != FileServices::TYPE_SPECIALIST_AVATAR) {
                return $context
                    ->buildViolation('Этот файл использовать нельзя!')
                    ->addViolation();
            }
            if (!$user->getIsAdmin()) {
                return $context
                    ->buildViolation('Этот файл создал пользователь!')
                    ->addViolation();
            }
            if ($avatar->getIsDeleted()) {
                return $context
                    ->buildViolation('Файл удален!')
                    ->addViolation();
            }
        }
    }

    public function validateEmail($email, ExecutionContextInterface $context)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $context
                ->buildViolation('Введите корректный e-mail!')
                ->addViolation();
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
