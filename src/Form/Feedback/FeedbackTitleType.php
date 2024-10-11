<?php

namespace App\Form\Feedback;

use App\Entity\Feedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\FormServices;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\FilesRepository;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Форма для редактирования заголовка обратной связи
 *
 * @package App\Form
 */
class FeedbackTitleType extends AbstractType
{
    private UserRepository $userRepository;
    private CoreSecurity $security;

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
            ->add('title', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateTitle']),
                ]
            ])
            ->add('feedback_id', EntityType::class, [
                'class' => Feedback::class,
                'choice_label' => 'id',
                'invalid_message' => 'Заявка не найдена',
                'constraints' => [
                    new Callback([$this, 'validateFeedback']),
                ]
            ]);
        ;
    }

    public function validateTitle($title, ExecutionContextInterface $context)
    {
        $value = trim($title);
        if (empty($value)) {
            return $context
                ->buildViolation('Нужно заполнить заголовок')
                ->addViolation();
        }
        if (mb_strlen($title, 'utf-8') < 5) {
            return $context
                ->buildViolation('Заголовок не может быть меньше 5 символов')
                ->addViolation();
        }
        if (mb_strlen($title, 'utf-8') > 255) {
            return $context
                ->buildViolation('Заголовок не может быть больше 255 символов')
                ->addViolation();
        }
    }

    public function validateFeedback($feedback, ExecutionContextInterface $context)
    {
        if ($feedback->getStatus()) {
            return $context
                ->buildViolation('Обращение закрыто')
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
