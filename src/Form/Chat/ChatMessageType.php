<?php

namespace App\Form\Chat;

use App\Entity\Chat;
use App\Entity\ChatMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use App\Form\FormServices;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Форма отправки сообщения в чат
 *
 * @package App\Form
 */
class ChatMessageType extends AbstractType
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
            ->add('chat_id', EntityType::class, [
                "class" => Chat::class,
                'invalid_message' => 'Чат не найден',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите chat ID',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите chat ID',
                    ]),
                ],
            ])
            ->add('message_id', EntityType::class, [
                "class" => ChatMessage::class
            ])
            ->add('message', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'max' => 4000,
                        'maxMessage' => 'Сообщение не может быть больше 4000 символов',
                    ]),
                    new Callback([$this, 'validateMessage'])
                ]
            ])
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'constraints' => [
                    new Assert\Count([
                        'max' => 10,
                        'maxMessage' => 'Максимальное количество фото - 10',
                    ]),
                    new Callback([$this->formServices, 'validateFileIds']),
                ]
            ]);
    }

    public function validateMessage($value, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $value = (trim($value));
        $files = $form->get('files')->getData();
        if (empty($files)) {
            if (empty($value)) {
                return $context
                    ->buildViolation('Введите текст сообщения')
                    ->addViolation();
            }
            if (mb_strlen($value, 'utf-8') < 2) {
                return $context
                    ->buildViolation('Сообщение не может быть меньше 2 символов')
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
