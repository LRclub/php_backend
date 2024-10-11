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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\FormServices;

/**
 * Форма для редактирования профиля
 *
 * @package App\Form
 */
class UpdateUserType extends AbstractType
{
    protected UserRepository $userRepository;
    protected CoreSecurity $security;
    protected FormServices $formServices;
    protected CountriesRepository $countriesRepository;

    public function __construct(
        UserRepository $userRepository,
        CoreSecurity $security,
        FormServices $formServices,
        CountriesRepository $countriesRepository
    ) {
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->formServices = $formServices;
        $this->countriesRepository = $countriesRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('patronymic_name', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 60,
                            'minMessage' => "Отчество должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "Отчество не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                    new Callback([$this->formServices, 'validateFIO'])
                ],
            ])
            ->add('first_name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Нужно указать имя'
                    ]),
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 50,
                            'minMessage' => "Имя должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "Имя не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                    new Callback([$this->formServices, 'validateFIO'])
                ],
            ])
            ->add('last_name', TextType::class, [
                'constraints' => [
                    /*new Assert\NotBlank([
                        'message' => 'Нужно указать фамилию'
                    ]),*/
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 50,
                            'minMessage' => "Фамилия должна содержать минимум {{ limit }} символа",
                            'maxMessage' => "Фамилия не должна быть длиннее {{ limit }} символов",
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
            ->add('birthday', DateType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                'constraints' => [
                    /*new Assert\NotBlank([
                        'message' => 'Дата рождения не указана'
                    ]),
                    new Assert\NotNull([
                        'message' => 'Дата рождения не указана'
                    ]),*/
                    new Callback([$this, 'validateBirthday'])
                ],
            ))
            ->add('roles')
            ->add('avatar', EntityType::class, [
                'class' => Files::class,
                'constraints' => [
                    new Callback([$this->formServices, 'validateFileId'])
                ],
            ])
            ->add('interests', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 5000,
                            'maxMessage' => "Интересы не должны быть длиннее {{ limit }} символов",
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
                        ]
                    ),
                ],
            ])

            ->add('country', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validiateCountry'])
                ],
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateCity']),
                    new Assert\Length(
                        [
                            'max' => 255,
                            'maxMessage' => "Город не должен быть длиннее {{ limit }} символов",
                        ]
                    ),
                ],
            ])
            ->add('vk', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateVK'])
                ],
            ])
            ->add('ok', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateOK'])
                ],
            ])
            ->add('telegram', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateTG'])
                ],
            ])
            ->add('instagram', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateInst'])
                ],
            ])

            ->add('email_new_materials')
            ->add('email_notice')
            ->add('email_subscription_history');
    }

    /**
     * @param mixed $value
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validatePhone($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();

        return $this->phoneConstraints($user, $context);
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

        return $this->emailConstraints(strval($email), $user, $context);
    }

    /**
     * @param mixed $value
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateBirthday($value, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $birthday = $form->get('birthday')->getData();

        // Validate year
        if (!empty($birthday)) {
            $year = (int)$birthday->format('Y');

            if (date("Y") - 100 > $year) {
                return $context
                    ->buildViolation('Год указан неверно!')
                    ->addViolation();
            }

            if (date_diff($birthday, date_create('now'))->y < 18) {
                return $context
                    ->buildViolation('Вам меньше 18 лет!')
                    ->addViolation();
            }
        }
    }


    public function validateOK($value, ExecutionContextInterface $context)
    {
        return $this->domainConstraints($context, $value, 'ok.ru');
    }

    public function validateVK($value, ExecutionContextInterface $context)
    {
        return $this->domainConstraints($context, $value, 'vk.com');
    }

    public function validateTG($value, ExecutionContextInterface $context)
    {
        return $this->domainConstraints($context, $value, 't.me');
    }

    public function validateInst($value, ExecutionContextInterface $context)
    {
        return $this->domainConstraints($context, $value, 'instagram.com');
    }

    /**
     * Проверка на наличие страны
     *
     * @param ExecutionContextInterface $context
     * @param $country_id
     * @return null
     */
    public function validiateCountry($country_id, ExecutionContextInterface $context)
    {
        if (empty($country_id)) {
            return null;
        }

        if (!is_numeric($country_id)) {
            return $context
                ->buildViolation('Введите ID страны!')
                ->addViolation();
        }

        $country = $this->countriesRepository->find(intval($country_id));

        if (empty($country)) {
            return $context
                ->buildViolation('Страна не найдена!')
                ->addViolation();
        }
    }

    /**
     * Проверка города
     *
     * @param $city
     * @param ExecutionContextInterface $context
     * @return mixed
     */
    public function validateCity($city, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $country = $form->get('country')->getData();

        if (!empty($city) && empty($country)) {
            return $context
                ->buildViolation('Для установки города, выберите страну')
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

    /**
     * Проверка домена
     *
     * @param ExecutionContextInterface $context
     * @param string $url
     * @param string $host
     * @return mixed
     */
    protected function domainConstraints(ExecutionContextInterface $context, ?string $url, string $host)
    {
        $url = trim($url);
        $host_url = parse_url($url, PHP_URL_HOST);
        $host_scheme = parse_url($url, PHP_URL_SCHEME);
        $host_exists = ($host_url == $host || $host_url == 'www.' . $host)
            && ($host_scheme == 'https' || $host_scheme == 'http');

        if (!$host_exists && !empty($url)) {
            return $context
                ->buildViolation('Укажите адрес содержащий "' . $host . '"')
                ->addViolation();
        }
    }


    /**
     * Метод для проверки телефона, наследуется в админ панели для проверки формы
     *
     * @param UserInterface $user
     * @param ExecutionContextInterface $context
     * @return mixed
     */
    protected function phoneConstraints(UserInterface $user, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $phone = $form->get('phone')->getData();

        if ($user->getPhone() != $phone) {
            return $context
                ->buildViolation('В профиле нельзя редактировать номер')
                ->addViolation();
        }
    }

    /**
     * Метод для проверки почты, наследуется в админ панели для проверки формы
     *
     * @param string $email
     * @param UserInterface $user
     * @param ExecutionContextInterface $context
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function emailConstraints(string $email, UserInterface $user, ExecutionContextInterface $context)
    {
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
}
