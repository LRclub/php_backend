<?php

namespace App\Form\Journal;

use App\Entity\JournalNotes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Форма для создания/редактирования заметки
 *
 * @package App\Form
 */
class JournalNotesType extends AbstractType
{
    private CoreSecurity $security;

    public function __construct(
        CoreSecurity $security
    ) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', EntityType::class, [
                'class' => JournalNotes::class,
                'invalid_message' => 'Заметка не найдена',
                'constraints' => [
                    new Callback([$this, 'validateNote']),
                ]
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Заголовок не может быть меньше 2 символов',
                        'maxMessage' => 'Заголовок не может быть больше 255 символов',
                    ]),
                ]
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Нужно указать описание',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Нужно указать описание',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 500,
                        'minMessage' => 'Описание не может быть меньше 2 символов',
                        'maxMessage' => 'Описание не может быть больше 500 символов',
                    ]),
                ]
            ]);
    }

    /**
     * @param mixed $note
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateNote($note, ExecutionContextInterface $context)
    {
        if ($note) {
            if ($note->getIsDeleted()) {
                return $context
                    ->buildViolation('Заметка удалена')
                    ->addViolation();
            }

            $user = $this->security->getUser();
            if ($note->getUser() != $user) {
                return $context
                    ->buildViolation('Заметка не найдена')
                    ->addViolation();
            }
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
