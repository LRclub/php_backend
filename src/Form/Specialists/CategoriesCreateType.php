<?php

namespace App\Form\Specialists;

use App\Entity\Consultations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Form\FormServices;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Repository\SpecialistsRepository;
use App\Repository\SpecialistsCategoriesRepository;
use App\Repository\ConsultationsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CategoriesCreateType extends AbstractType
{
    private ParameterBagInterface $params;
    private FormServices $formServices;
    private SpecialistsRepository $specialistsRepository;
    private SpecialistsCategoriesRepository $specialistsCategoriesRepository;
    private ConsultationsRepository $consultationsRepository;

    public function __construct(
        ParameterBagInterface $params,
        FormServices $formServices,
        SpecialistsRepository $specialistsRepository,
        SpecialistsCategoriesRepository $specialistsCategoriesRepository,
        ConsultationsRepository $consultationsRepository
    ) {
        $this->params = $params;
        $this->formServices = $formServices;
        $this->specialistsRepository = $specialistsRepository;
        $this->specialistsCategoriesRepository = $specialistsCategoriesRepository;
        $this->consultationsRepository = $consultationsRepository;
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
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => "Категория должна содержать минимум {{ limit }} символа",
                            'maxMessage' => "Категория не должна быть длиннее {{ limit }} символов",
                        ]
                    ),
                    new Callback([$this, 'validateName']),
                ],
            ])
            ->add('specialists_ids', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'constraints' => [
                    new Callback([$this, 'validateSpecialists']),
                ]
            ])
            ->add('consultation_id', EntityType::class, [
                "class" => Consultations::class,
                'invalid_message' => 'Категория не найдена',
            ]);
    }

    /**
     * @param mixed $name
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateName($name, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $consultation = $form->get('consultation_id')->getData();

        if ($name) {
            $category = $this->consultationsRepository->findOneBy([
                'name' => $name,
                'is_deleted' => false
            ]);
            if ($category != $consultation) {
                if ($category) {
                    return $context
                        ->buildViolation('Категория с таким названием уже существует')
                        ->addViolation();
                }
            }
        }
    }

    /**
     * @param mixed $specialists_ids
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateSpecialists($specialists_ids, ExecutionContextInterface $context)
    {
        if ($specialists_ids) {
            foreach ($specialists_ids as $specialist_id) {
                $specialist = $this->specialistsRepository->find($specialist_id);
                if (!$specialist) {
                    return $context
                        ->buildViolation('Специалист с ID ' . $specialist_id . ' не найден!')
                        ->addViolation();
                }

                if ($specialist->getIsDeleted()) {
                    return $context
                        ->buildViolation('Специалист с ID ' . $specialist_id . ' удален!')
                        ->addViolation();
                }
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
