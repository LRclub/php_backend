<?php

namespace App\Form\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\User\UserServices;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\FormServices;

/**
 * Форма для обновления профиля после регистрации
 *
 * @package App\Form
 */
class InitUserDataType extends AbstractType
{
    private UserRepository $userRepository;
    private CoreSecurity $security;
    private FormServices $formServices;

    public function __construct(
        UserRepository $userRepository,
        CoreSecurity $security,
        FormServices $formServices
    ) {
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->formServices = $formServices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Callback([$this, 'validateEmail'], ['groups' => 'InitUser'])
                ],
            ])
            ->add('gender')
            ->add('patronymic_name', TextType::class, [
                'constraints' => [
                    new Callback([$this->formServices, 'validateFIO'], ['groups' => 'InitUser'])
                ],
            ])
            ->add('first_name', TextType::class, [
                'constraints' => [
                    new Callback([$this->formServices, 'validateFIO'], ['groups' => 'InitUser'])
                ],
            ])
            ->add('last_name', TextType::class, [
                'constraints' => [
                    new Callback([$this->formServices, 'validateFIO'], ['groups' => 'InitUser'])
                ],
            ]);
    }

    public function validateEmail($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        $form = $context->getObject()->getParent();
        $email = $form->get('email')->getData();

        if (empty($email)) {
            return $context
                ->buildViolation('Введите e-mail')
                ->addViolation();
        }

        if ($this->userRepository->checkUniqueEmail($user->getId(), $email)) {
            return $context
                ->buildViolation('Данный e-mail уже используется другим пользователем')
                ->addViolation();
        }
    }

    public function validateFIO($value, ExecutionContextInterface $context)
    {
        if (preg_match("/[^a-zа-яё ]/iu", $value)) {
            return $context
                ->buildViolation('В написании допустимы только буквы латинского и русского алфавита!')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['InitUser'],
        ]);
    }
}
