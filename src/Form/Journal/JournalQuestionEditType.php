<?php

namespace App\Form\Journal;

use App\Repository\JournalQuestionsRepository;
use App\Services\DateServices;
use App\Services\Journal\JournalServices;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\JournalQuestions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Форма для редактирования вопроса дневника
 *
 * @package App\Form
 */
class JournalQuestionEditType extends AbstractType
{
    private JournalQuestionsRepository $journalQuestionsRepository;

    public function __construct(
        JournalQuestionsRepository $journalQuestionsRepository
    ) {
        $this->journalQuestionsRepository = $journalQuestionsRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', EntityType::class, [
                'class' => JournalQuestions::class,
                'invalid_message' => 'Вопрос не найден',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите ID вопроса',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите ID вопроса',
                    ])
                ],
            ])
            ->add('question_text', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Нужно указать вопрос',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Нужно указать вопрос',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Вопрос не может быть меньше 2 символов',
                        'maxMessage' => 'Вопрос не может быть больше 255 символов',
                    ]),
                ]
            ])
            ->add('sort', IntegerType::class, [
                'invalid_message' => 'Введите числовое значение',
                'constraints' => [
                    new Callback([$this, 'validateSort']),
                ]
            ])
        ;
    }

    /**
     * @param mixed $date
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateSort($value, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $question = $form->get('question')->getData();

        if (!empty($value) && $question->getType() != JournalServices::JOURNAL_ANSWER_REPORT) {
            return $context
                ->buildViolation('Сортировка используется только в ' . JournalServices::JOURNAL_ANSWER_REPORT)
                ->addViolation();
        }

        if ($question->getType() == JournalServices::JOURNAL_ANSWER_REPORT) {
            if (!is_numeric($value)) {
                return $context
                    ->buildViolation('Введите числовое значение сортировки!')
                    ->addViolation();
            }

            if ($question->getIsDeleted()) {
                return $context
                    ->buildViolation('Нельзя отредактировать удаленный вопрос')
                    ->addViolation();
            }

            $this->checkUniqueSortValue($value, $question, $context);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    protected function checkUniqueSortValue($value, $question, $context)
    {
        $questions = $this->journalQuestionsRepository->getQuestionsByType(JournalServices::JOURNAL_ANSWER_REPORT);

        $sort_values = array_map(function ($item) use ($question) {
            if ($question && $item['id'] == $question->getId()) {
                return null;
            }
            return $item['sort'];
        }, $questions);

        if (in_array($value, $sort_values)) {
            return $context
                ->buildViolation('Выберите уникальное значение сортировки! Значение "' . $value . '" уже используется')
                ->addViolation();
        }
    }
}
