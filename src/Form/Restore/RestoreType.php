<?php

namespace App\Form\Restore;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Форма для проверки email
 *
 * @package App\Form
 */
class RestoreType extends AbstractType
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ]);
    }

    public function validateEmail($email, ExecutionContextInterface $context)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $context
                ->buildViolation('Введите корректный e-mail!')
                ->addViolation();
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
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
