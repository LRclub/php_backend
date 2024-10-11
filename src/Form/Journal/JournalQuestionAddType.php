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
 * Форма для добавления вопроса дневника
 *
 * @package App\Form
 */
class JournalQuestionAddType extends JournalQuestionEditType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
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

    public function validateSort($value, ExecutionContextInterface $context)
    {
        $this->checkUniqueSortValue($value, null, $context);
    }
}
