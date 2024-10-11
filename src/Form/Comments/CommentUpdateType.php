<?php

namespace App\Form\Comments;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use App\Form\FormServices;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\FilesRepository;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\CommentsCollectorRepository;
use App\Entity\CommentsCollector;
use App\Entity\Comments;

/**
 * Форма для создания комментария
 *
 * @package App\Form
 */
class CommentUpdateType extends AbstractType
{
    private UserRepository $userRepository;
    private CoreSecurity $security;

    public function __construct(
        CommentsCollectorRepository $commentsCollectorRepository,
        CoreSecurity $security,
        FormServices $formServices
    ) {
        $this->commentsCollectorRepository = $commentsCollectorRepository;
        $this->security = $security;
        $this->formServices = $formServices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment_id', EntityType::class, [
                'class' => Comments::class,
                'required' => true,
                'invalid_message' => 'Комментарий не найден',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Нужно указать ID комментария',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Нужно указать ID комментария',
                    ]),
                    new Callback([$this, 'validateEditUser']),
                ],
            ])
            ->add('text', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите текст комментария',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите текст комментария',
                    ]),
                    new Assert\Length(
                        [
                            'min' => 1,
                            'max' => 1000,
                            'minMessage' => "Комментарий должен содержать минимум {{ limit }} символа",
                            'maxMessage' => "Комментарий не должен быть длиннее {{ limit }} символов",
                        ]
                    )
                ],
            ]);
    }

    /**
     *
     * @param mixed $edit_comment_id
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateEditUser($comment, ExecutionContextInterface $context)
    {
        if (!empty($comment)) {
            $user = $this->security->getUser();
            if ($user != $comment->getUser()) {
                if (!$user->getIsModerator() && !$user->getIsAdmin()) {
                    return $context
                        ->buildViolation('Нельзя редактировать чужой комментарий')
                        ->addViolation();
                }
            }

            if ($comment->getIsDeleted()) {
                return $context
                    ->buildViolation('Нельзя редактировать удаленный комментарий')
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
