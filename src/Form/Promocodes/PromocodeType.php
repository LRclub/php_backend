<?php

namespace App\Form\Promocodes;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\PromocodesRepository;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use App\Services\Marketing\PromocodeAction\PromocodeActionFactory;
use App\Services\Marketing\PromocodeServices;
use Container0hUasbU\getPromocodeActionFactoryService;

class PromocodeType extends AbstractType
{
    private ParameterBagInterface $params;

    public function __construct(
        ParameterBagInterface $params,
        PromocodesRepository $promocodesRepository,
        PromocodeActionFactory $promocodeActionFactory
    ) {
        $this->params = $params;
        $this->promocodesRepository = $promocodesRepository;
        $this->promocodeActionFactory = $promocodeActionFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите промокод',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите промокод',
                    ]),
                    new Callback([$this, 'validateCode'])
                ],
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\Length(
                        [
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => "Описание должно содержать минимум {{ limit }} символа",
                            'maxMessage' => "Описание не должно быть длиннее {{ limit }} символов",
                        ]
                    ),
                ],
            ])
            ->add('start_time', DateType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                'constraints' => [
                    new Callback([$this, 'validateStartTime'])
                ],
            ))
            ->add('end_time', DateType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                'constraints' => [
                    new Callback([$this, 'validateEndTime'])
                ],
            ))
            ->add('amount', IntegerType::class, [])
            ->add('action', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите action',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите action',
                    ]),
                    new Callback([$this, 'validateAction'])
                ],
            ])
            ->add('discount_percent', RangeType::class, [
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 99,
                        'minMessage' => 'Скидка не может быть меньше 1%',
                        'maxMessage' => 'Скидка не может быть больше 99%',
                    ]),
                    new Callback([$this, 'validateDiscountPercent'])
                ]
            ])
            ->add('promocode_id', IntegerType::class, ['constraints' => [
                new Callback([$this, 'validateId'])
            ],])
            ->add('is_active', IntegerType::class, []);
    }

    public function validateDiscountPercent($discount_percent, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $action = $form->get('action')->getData();
        if ($discount_percent) {
            if ($action != PromocodeServices::DISCOUNT_CODE) {
                return $context
                    ->buildViolation('Нужно указать другой тип промокода')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $code
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateCode($code, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $action = $form->get('action')->getData();
        $promocode_id = null;
        if (null != $form->get('promocode_id')->getData()) {
            $promocode_id = $form->get('promocode_id')->getData();
        }
        if ($promocode_id) {
            $promocode = $this->promocodesRepository->findOneBy(['code' => $code, 'action' => $action]);
            if ($promocode && $promocode->getId() != $promocode_id) {
                return $context
                    ->buildViolation('Промокод уже существует')
                    ->addViolation();
            }
        } else {
            $promocode = $this->promocodesRepository->findOneBy(['code' => $code, 'action' => $action]);
            if ($promocode) {
                return $context
                    ->buildViolation('Промокод уже существует')
                    ->addViolation();
            }
        }
    }


    /**
     * @param mixed $code
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateId($id, ExecutionContextInterface $context)
    {
        if (!empty($id) && !$this->promocodesRepository->find($id)) {
            return $context
                ->buildViolation('Промокод не найден')
                ->addViolation();
        }
    }

    /**
     * @param mixed $action
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateAction($action, ExecutionContextInterface $context)
    {
        $actions = $this->promocodeActionFactory->getActionNames();
        if (!in_array(ucfirst($action), $actions)) {
            return $context
                ->buildViolation('Такой action не существует')
                ->addViolation();
        }
    }

    /**
     * @param mixed $create_time
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateStartTime($start_time, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $end_time = $form->get('end_time')->getData();
        if ($start_time) {
            if ($start_time->getTimestamp() + 24 * 60 * 60 < time()) {
                return $context
                    ->buildViolation('Нельзя устанавливать прошедшую дату!')
                    ->addViolation();
            }
            if ($end_time) {
                if ($start_time->getTimestamp() > $end_time->getTimestamp()) {
                    return $context
                        ->buildViolation('Дата начала больше даты окончания!')
                        ->addViolation();
                }
                if ($start_time->getTimestamp() == $end_time->getTimestamp()) {
                    return $context
                        ->buildViolation('Дата начала равна дате окончания!')
                        ->addViolation();
                }
            }
        }
    }

    /**
     * @param mixed $create_time
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateEndTime($end_time, ExecutionContextInterface $context)
    {
        if ($end_time) {
            if ($end_time->getTimestamp() + 24 * 60 * 60 < time()) {
                return $context
                    ->buildViolation('Нельзя устанавливать прошедшую дату!')
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
