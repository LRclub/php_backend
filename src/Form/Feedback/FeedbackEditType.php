<?php

namespace App\Form\Feedback;

use App\Entity\FeedbackMessage;
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
use App\Repository\FilesRepository;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Форма для создания обратной связи
 *
 * @package App\Form
 */
class FeedbackEditType extends AbstractType
{
    private UserRepository $userRepository;
    private CoreSecurity $security;
    private FormServices $formServices;
    private FilesRepository $filesRepository;

    public function __construct(
        UserRepository $userRepository,
        FilesRepository $filesRepository,
        CoreSecurity $security,
        FormServices $formServices
    ) {
        $this->userRepository = $userRepository;
        $this->filesRepository = $filesRepository;
        $this->security = $security;
        $this->formServices = $formServices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feedback_message_id', EntityType::class, [
                "class" => FeedbackMessage::class,
                'invalid_message' => 'Сообщение не найдено',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите ID сообщения',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите ID сообщения',
                    ])
                ]
            ])
            ->add('message', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'max' => 20000,
                        'maxMessage' => 'Сообщение не может быть больше 20000 символов',
                    ]),
                    new Callback([$this, 'validateMessage']),
                ]
            ])
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'constraints' => [
                    new Assert\Count([
                        'max' => 50,
                        'maxMessage' => 'Максимальное количество фото - 50',
                    ]),
                    new Callback([$this->formServices, 'validateFileIds']),
                ]
            ]);
    }

    public function validateMessage($value, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $files = $form->get('files')->getData();
        if (empty($files)) {
            if (mb_strlen(trim($value), 'utf-8') < 1) {
                return $context
                    ->buildViolation('Сообщение не может быть пустым')
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
