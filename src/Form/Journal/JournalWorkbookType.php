<?php

namespace App\Form\Journal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Services\DateServices;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\JournalWorkbookRepository;

/**
 * Форма для редактирования workbook
 *
 * @package App\Form
 */
class JournalWorkbookType extends AbstractType
{
    private DateServices $dateServices;
    private CoreSecurity $security;
    private JournalWorkbookRepository $journalWorkbookRepository;

    public function __construct(
        DateServices $dateServices,
        CoreSecurity $security,
        JournalWorkbookRepository $journalWorkbookRepository
    ) {
        $this->dateServices = $dateServices;
        $this->security = $security;
        $this->journalWorkbookRepository = $journalWorkbookRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('goal', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        //'min' => 0,
                        'max' => 500,
                        //'minMessage' => 'Цель не может быть меньше 2 символов',
                        'maxMessage' => 'Цель не может быть больше 500 символов',
                    ]),
                    new Callback([$this, 'validateGoal']),
                ]
            ])
            ->add('result', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        //'min' => 2,
                        'max' => 500,
                        //'minMessage' => 'Результат не может быть меньше 2 символов',
                        'maxMessage' => 'Результат не может быть больше 500 символов',
                    ]),
                ]
            ])
            ->add('type', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите тип заполнения',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите тип заполнения',
                    ]),
                    new Callback([$this, 'validateType']),
                ],
            ])
            ->add('date', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите дату',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите дату',
                    ]),
                    new Callback([$this, 'validateDate']),
                ],
            ]);
    }

    /**
     * @param mixed $type
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateType($type, ExecutionContextInterface $context)
    {
        if (!in_array($type, ['week', 'month', 'year'])) {
            return $context
                ->buildViolation('Тип заполнения должен быть week, month, year')
                ->addViolation();
        }
    }

    /**
     * @param mixed $goal
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateGoal($goal, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $type = $form->get('type')->getData();
        $result = $form->get('result')->getData();
        $date = $form->get('date')->getData();
        $user = $this->security->getUser();

        $workbook = $this->journalWorkbookRepository->findUserWorkbook($user, $date, $type);
        if (!$workbook && empty($goal)) {
            return $context
                ->buildViolation('Нужно заполнить цель!')
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
        $form = $context->getObject()->getParent();
        $type = $form->get('type')->getData();

        if ($type == "year") {
            if (!$this->dateServices->validateYear($date)) {
                return $context
                    ->buildViolation('Введите корректный год в формате YYYY')
                    ->addViolation();
            }
        } elseif ($type == "month") {
            if (!$this->dateServices->validateMonth($date)) {
                return $context
                    ->buildViolation('Введите корректную дату в формате YYYY-MM')
                    ->addViolation();
            }
        } elseif ($type == "week") {
            if (!$this->dateServices->validateDate($date)) {
                return $context
                    ->buildViolation('Введите корректную дату в формате YYYY-MM-DD')
                    ->addViolation();
            }

            if (!$this->dateServices->validateDateIsSunday($date)) {
                return $context
                    ->buildViolation('Введите дату воскресенья для интервала дат')
                    ->addViolation();
            }
        } else {
            return $context
                ->buildViolation('Нужно указать корректный тип заполнения')
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
