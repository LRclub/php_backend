<?php

namespace App\Form\Notice;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use App\Form\FormServices;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\NoticeRepository;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\JsonValidator;
use function PHPUnit\Framework\isJson;

/**
 * Форма для создания уведомления
 *
 * @package App\Form
 */
class NoticeCreateType extends AbstractType
{
    public function __construct(
        UserRepository $userRepository,
        CoreSecurity $security,
        FormServices $formServices,
        NoticeRepository $noticeRepository
    ) {
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->formServices = $formServices;
        $this->noticeRepository = $noticeRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateType']),
                ]
            ])
            ->add('category', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateCategory']),
                ]
            ])
            ->add(
                $builder->create('data', FormType::class)->add('link', TextType::class)
            )
            ->add('message', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'max' => 20000,
                        'minMessage' => 'Сообщение не может быть меньше 2 символов',
                        'maxMessage' => 'Сообщение не может быть больше 1000 символов',
                    ])
                ]
            ]);
    }

    public function validateType($type, ExecutionContextInterface $context)
    {
        if (!in_array($type, $this->noticeRepository::NOTICE_TYPES)) {
            return $context
                ->buildViolation('Тип уведомления отсутствует в базе')
                ->addViolation();
        }
    }

    public function validateCategory($category, ExecutionContextInterface $context)
    {
        if (!in_array($category, $this->noticeRepository::CATEGORY_TYPES)) {
            return $context
                ->buildViolation('Категория уведомлений отсутствует в базе')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
