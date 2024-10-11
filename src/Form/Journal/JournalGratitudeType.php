<?php

namespace App\Form\Journal;

use App\Entity\JournalNotes;
use App\Repository\JournalQuestionsRepository;
use App\Services\DateServices;
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
 * Форма для создания/редактирования дневников благодарности
 *
 * @package App\Form
 */
class JournalGratitudeType extends AbstractType
{
    private CoreSecurity $security;
    private DateServices $dateServices;
    private JournalQuestionsRepository $journalQuestionsRepository;

    public function __construct(
        CoreSecurity $security,
        DateServices $dateServices,
        JournalQuestionsRepository $journalQuestionsRepository
    ) {
        $this->security = $security;
        $this->dateServices = $dateServices;
        $this->journalQuestionsRepository = $journalQuestionsRepository;
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
                        new Assert\NotBlank([
                            'message' => 'Текст не может быть пустой',
                        ]),
                        new Assert\NotNull([
                            'message' => 'Текст не может быть пустой',
                        ])
                    ]),
                    new Assert\Count([
                        'max' => 10,
                        'maxMessage' => 'Максимальное количество благодарностей - 10!',
                    ]),
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Укажите хотя бы одну благодарность!',
                    ]),
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
