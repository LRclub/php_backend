<?php

namespace App\Form\Restore;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use App\Services\SMSServices;
use App\Repository\UserRepository;

/**
 * Форма для проверки номеров телефона
 *
 * @package App\Form
 */
class RestorePhoneType extends AbstractType
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phone', TextType::class, [])
            ->add('phone_repeat', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validatePhones'])
                ],
            ])
            ->add('user_id', IntegerType::class, [
                'required' => true
            ])
            ->add('code', TextType::class, [
                'required' => true
            ]);
    }

    public function validatePhones($value, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $phone = $form->get('phone')->getData();
        $phone_repeat = $form->get('phone_repeat')->getData();

        if ($phone !== $phone_repeat) {
            return $context
                ->buildViolation('Номера телефона не совпадают')
                ->addViolation();
        }

        if ($this->userRepository->findByPhone($phone)) {
            return $context
                ->buildViolation('Пользователь с таким номером уже существует')
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
