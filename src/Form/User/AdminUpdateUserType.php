<?php

namespace App\Form\User;

use App\Entity\Files;
use App\Repository\CountriesRepository;
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
use App\Form\User\UpdateUserType;
use Symfony\Component\Form\Exception\OutOfBoundsException;

/**
 * Форма для редактирования профиля админом
 *
 * @package App\Form
 */
class AdminUpdateUserType extends UpdateUserType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['data']['user'];

        $builder
            ->add('patronymic_name', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 60,
                            'minMessage' => "Отчество должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "Отчество не должно быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                    new Callback([$this->formServices, 'validateFIO'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('first_name', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 50,
                            'minMessage' => "Имя должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "Имя не должно быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                    new Callback([$this->formServices, 'validateFIO'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('last_name', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 50,
                            'minMessage' => "Фамилия должна содержать минимум {{ limit }} символа",
                            'maxMessage' => "Фамилия не должна быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                    new Callback([$this->formServices, 'validateFIO'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Нужно указать номер телефона',
                        'groups' => 'UpdateUserAdmin'
                    ]),
                    new Callback([$this, 'validatePhone'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('email', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateEmail'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('birthday', DateType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                'constraints' => [
                    new Callback([$this, 'validateBirthday'], ['groups' => 'UpdateUserAdmin'])
                ],
            ))
            ->add('roles')
            ->add('avatar', EntityType::class, [
                'class' => Files::class,
                'constraints' => [
                    new Callback([$this->formServices, 'validateFileId'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('interests', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 5000,
                            'maxMessage' => "Интересы не должны быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                ],
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 5000,
                            'maxMessage' => "Информация о себе не должна быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                ],
            ])
            ->add('super_power', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 5000,
                            'maxMessage' => "Информация не должна быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                ],
            ])
            ->add('principles', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 5000,
                            'maxMessage' => "Информация не должна быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                ],
            ])
            ->add('country', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validiateCountry'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateCity'], ['groups' => 'UpdateUserAdmin']),
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Город не должен быть длиннее {{ limit }} символов",
                            'groups' => 'UpdateUserAdmin'
                        ]
                    ),
                ],
            ])
            ->add('vk', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateVK'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('ok', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateOK'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('telegram', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateTG'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('instagram', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateInst'], ['groups' => 'UpdateUserAdmin'])
                ],
            ])
            ->add('user_id')
            ->add('admin_is_blocked')
            ->add('email_new_materials')
            ->add('email_notice')
            ->add('email_subscription_history');
        ;
    }

    /**
     * @param mixed $value
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateEmail($email, ExecutionContextInterface $context)
    {
        $user = $this->user;

        return $this->emailConstraints(strval($email), $user, $context);
    }

    /**
     * @param mixed $value
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validatePhone($value, ExecutionContextInterface $context)
    {
        $user = $this->user;

        return $this->phoneConstraints($user, $context);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['UpdateUserAdmin'],
        ]);
    }
}
