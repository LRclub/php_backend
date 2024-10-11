<?php

namespace App\Form\Journal;

use App\Entity\JournalNotes;
use App\Repository\JournalAnswersRepository;
use App\Repository\JournalQuestionsRepository;
use App\Services\DateServices;
use App\Services\HelperServices;
use App\Services\Journal\JournalServices;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Форма для создания/редактирования дневников Отчета дня
 *
 * @package App\Form
 */
class JournalReportType extends AbstractType
{
    private CoreSecurity $security;
    private DateServices $dateServices;
    private JournalQuestionsRepository $journalQuestionsRepository;
    private JournalAnswersRepository $journalAnswersRepository;

    public function __construct(
        CoreSecurity $security,
        DateServices $dateServices,
        JournalQuestionsRepository $journalQuestionsRepository,
        JournalAnswersRepository $journalAnswersRepository
    ) {
        $this->security = $security;
        $this->dateServices = $dateServices;
        $this->journalQuestionsRepository = $journalQuestionsRepository;
        $this->journalAnswersRepository = $journalAnswersRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('answer', CollectionType::class, [
                'allow_add' => true,
                'constraints' => [
                    new Assert\All([
                        new Assert\Length([
                            'min' => 2,
                            'max' => 1000,
                            'minMessage' => 'Ответ не может быть меньше 2 символов',
                            'maxMessage' => 'Максимальная длина ответа 1000 символов',
                        ]),
                        // new Assert\NotBlank([
                        //     'message' => 'Текст не может быть пустой',
                        // ]),
                        // new Assert\NotNull([
                        //     'message' => 'Текст не может быть пустой',
                        // ])
                    ]),
                    new Assert\Count([
                        'max' => 100,
                        'maxMessage' => 'Максимальное количество благодарностей - 100!',
                    ]),
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Укажите хотя бы одну благодарность!',
                    ]),
                    new Callback([$this, 'validateCountQuestions']),
                ]
            ])
            ->add('date', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Заголовок не может быть меньше 2 символов',
                        'maxMessage' => 'Заголовок не может быть больше 255 символов',
                    ]),
                    new Callback([$this, 'validateDate']),
                ]
            ]);
    }

    /**
     * Проверка для нескольких вопросов, в дневниках благодарности всегда один, но несколько ответов
     *
     * @param mixed $date
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateCountQuestions($answers, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        $form = $context->getObject()->getParent();
        $date = $form->get('date')->getData();
        $date = $this->dateServices->validateDate($date);

        if (!$date) {
            return $context
                ->buildViolation('Ошибка конвертации даты!')
                ->addViolation();
        }

        $result = $this->journalAnswersRepository->findOneBy([
            'user' => $user,
            'question_type' => JournalServices::JOURNAL_ANSWER_REPORT,
            'date' => $date
        ]);

        if ($result) {
            $question = HelperServices::isJson($result->getQuestion()) ? $result->getQuestion() : '[]';

            $questions = json_decode($question, true);
        } else {
            $questions = $this->journalQuestionsRepository->getQuestionsByType(JournalServices::JOURNAL_ANSWER_REPORT);
        }

        if (count($answers) != count($questions)) {
            return $context
                ->buildViolation('Количество ответов не совпадает с количеством вопросов!')
                ->addViolation();
        }
    }

    /**
     * @param mixed $date
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateDate($date, ExecutionContextInterface $context)
    {
        $date_val = $this->dateServices->validateDate($date);

        if (!$date_val) {
            return $context
                ->buildViolation('Введите корректную дату в формате YYYY-MM-DD')
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
