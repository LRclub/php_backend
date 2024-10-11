<?php

namespace App\Form\Materials;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use App\Form\FormServices;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Entity\MaterialsCategories;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Форма для редактирования категории
 *
 * @package App\Form
 */
class MaterialsCategoryEditType extends AbstractType
{
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
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите название категории',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите название категории',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Название не может быть меньше 2 символов',
                        'maxMessage' => 'Название не может быть больше 255 символов',
                    ])
                ]
            ])
            ->add('category_id', EntityType::class, [
                'class' => MaterialsCategories::class,
                'invalid_message' => 'Категория не найдена',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите ID категории',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите ID категории',
                    ]),
                ]
            ])
            ->add('parent_id', EntityType::class, [
                'class' => MaterialsCategories::class,
                'invalid_message' => 'Категория не найдена',
                'constraints' => [
                    new Callback([$this, 'validateParent'])
                ]
            ]);
    }

    /**
     * @param mixed $slug
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateParent($parent, ExecutionContextInterface $context)
    {
        if (!empty($parent)) {
            if ($parent->getParent()) {
                return $context
                    ->buildViolation('Нельзя добавить эту категорию')
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
