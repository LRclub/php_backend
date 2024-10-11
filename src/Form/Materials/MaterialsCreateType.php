<?php

namespace App\Form\Materials;

// Entity
use App\Entity\Files;
use App\Entity\Materials;
use App\Entity\MaterialsCategories;
// Form
use App\Form\FormServices;
// Services
use App\Services\Materials\MaterialsServices;
use App\Services\Providers\FacecastServices;
use App\Services\File\FileServices;
// Repository
use App\Repository\MaterialsRepository;
// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Форма для создания материала
 *
 * @package App\Form
 */
class MaterialsCreateType extends AbstractType
{
    private CoreSecurity $security;
    private FormServices $formServices;
    private MaterialsServices $materialsServices;
    private FacecastServices $facecastServices;
    private MaterialsRepository $materialsRepository;

    public function __construct(
        CoreSecurity $security,
        FormServices $formServices,
        MaterialsServices $materialsServices,
        FacecastServices $facecastServices,
        MaterialsRepository $materialsRepository
    ) {
        $this->security = $security;
        $this->formServices = $formServices;
        $this->materialsServices = $materialsServices;
        $this->facecastServices = $facecastServices;
        $this->materialsRepository = $materialsRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('material_id', EntityType::class, [
                "class" => Materials::class,
                'invalid_message' => 'Материал не найден',
                'constraints' => [
                    new Callback([$this, 'validateMaterial'])
                ]
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите название материала',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите название материала',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Название не может быть меньше 2 символов',
                        'maxMessage' => 'Название не может быть больше 255 символов',
                    ])
                ]
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'max' => 5000,
                        'minMessage' => 'Описание не может быть меньше 2 символов',
                        'maxMessage' => 'Описание не может быть больше 50000 символов',
                    ])
                ]
            ])
            ->add('short_description', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'max' => 500,
                        'minMessage' => 'Краткое описание не может быть меньше 2 символов',
                        'maxMessage' => 'Краткое описание не может быть больше 500 символов',
                    ])
                ]
            ])
            ->add('preview_image_id', EntityType::class, [
                'class' => Files::class,
                'invalid_message' => 'Укажите изображение для превью',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите изображение для превью',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите изображение для превью',
                    ]),
                    new Callback([$this->formServices, 'validateFileIds']),
                ]
            ])
            ->add('access', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Укажите тип доступа к материалу',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Укажите тип доступа к материалу',
                    ]),
                    new Callback([$this, 'validateAccess']),
                ]
            ])
            ->add('lazy_post', DateTimeType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                'constraints' => [
                    new Callback([$this, 'validateDate'])
                ],
            ))
            ->add('is_show_bill', CheckboxType::class, [
                'required' => false,
            ])
            ->add('category_id', EntityType::class, [
                "class" => MaterialsCategories::class,
                'invalid_message' => 'Категория не найдена',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите ID категории',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите ID категории',
                    ]),
                    new Callback([$this, 'validateCategory']),
                ],
            ])
            ->add('type', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Введите тип материала',
                    ]),
                    new Assert\NotNull([
                        'message' => 'Введите тип материала',
                    ]),
                    new Callback([$this, 'validateType']),
                ]
            ])
            ->add('video_id', EntityType::class, [
                'class' => Files::class,
                'invalid_message' => 'Нужно добавить видео',
                'constraints' => [
                    new Callback([$this, 'validateVideo']),
                ]
            ])
            ->add('audio_id', EntityType::class, [
                'class' => Files::class,
                'invalid_message' => 'Нужно добавить аудио',
                'constraints' => [
                    new Callback([$this, 'validateAudio']),
                ]
            ])
            ->add('article_files', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'constraints' => [
                    new Assert\Count([
                        'max' => 50,
                        'maxMessage' => 'Максимальное количество файлов - 10',
                    ]),
                    new Callback([$this->formServices, 'validateFileIds']),
                ]
            ])
            ->add('stream_url', TextType::class, [
                'constraints' => [
                    new Callback([$this, 'validateStream']),
                ]
            ])
            ->add('stream_start', DateTimeType::class, array(
                'widget' => 'single_text',
                "required" => true,
                'html5' => false,
                'constraints' => [
                    new Callback([$this, 'validateStreamStart'])
                ],
            ))
            ->add('is_stream_finished', CheckboxType::class, [
                'required' => false,
            ]);
    }

    public function validateMaterial($material, ExecutionContextInterface $context)
    {
        if (!empty($material) && $material->getIsDeleted()) {
            return $context
                ->buildViolation('Материал удален')
                ->addViolation();
        }
    }

    /**
     * @param mixed $audio
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateStream($stream_url, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $type = $form->get('type')->getData();
        $material = $form->get('material_id')->getData();
        $is_stream_finished = $form->get('is_stream_finished')->getData();

        if ($type == $this->materialsServices::TYPE_STREAM) {
            if (empty($stream_url)) {
                return $context
                    ->buildViolation('Нужно указать ссылку на эфир')
                    ->addViolation();
            }

            if (filter_var($stream_url, FILTER_VALIDATE_URL) === false) {
                return $context
                    ->buildViolation('Ссылка на эфир не валидна')
                    ->addViolation();
            }

            if (!strstr($stream_url, 'https://facecast.net/w/') !== false) {
                return $context
                    ->buildViolation('Ссылка facecast указана некорректно')
                    ->addViolation();
            }

            if ($material && $is_stream_finished && $stream_url != $material->getStream()) {
                return $context
                    ->buildViolation('Эфир завершен, нельзя редактировать ссылку')
                    ->addViolation();
            }

            $event_id = $this->facecastServices->getEventIdByUrl($stream_url);

            if ($material && !$material->getIsStreamFinished() && !$event_id && $stream_url != $material->getStream()) {
                return $context
                    ->buildViolation('Стрим не найден на Fasecast')
                    ->addViolation();
            }

            $exist_link = $this->materialsRepository->findOneBy(['stream' => $stream_url]);
            if ($exist_link && $exist_link != $material && !$exist_link->getIsStreamFinished()) {
                return $context
                    ->buildViolation('Ссылка на данный эфир уже добавлена в материалы')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $audio
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateAudio($audio_id, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $type = $form->get('type')->getData();

        if (
            $type == $this->materialsServices::TYPE_AUDIO ||
            $type == $this->materialsServices::TYPE_MEDITATION
        ) {
            if (empty($audio_id)) {
                return $context
                    ->buildViolation('Нужно добавить аудиозапись!')
                    ->addViolation();
            }
        }

        if (!empty($audio_id)) {
            if ($audio_id->getFileType() != FileServices::TYPE_MATERIAL_AUDIO) {
                return $context
                    ->buildViolation('Некорректный тип файла!')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $video
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateVideo($video_id, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $type = $form->get('type')->getData();

        if ($type == $this->materialsServices::TYPE_VIDEO && empty($video_id)) {
            return $context
                ->buildViolation('Нужно добавить видеозапись!')
                ->addViolation();
        }

        if (!empty($video_id)) {
            if ($video_id->getFileType() != FileServices::TYPE_MATERIAL_VIDEO) {
                return $context
                    ->buildViolation('Некорректный тип файла!')
                    ->addViolation();
            }
        }
    }

    /**
     * @param mixed $category
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateCategory($category, ExecutionContextInterface $context)
    {
        if ($category && $category->getIsDeleted()) {
            return $context
                ->buildViolation('Категория удалена!')
                ->addViolation();
        }
    }

    /**
     * @param mixed $type
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateType($type, ExecutionContextInterface $context)
    {
        if (!in_array($type, $this->materialsServices::TYPES)) {
            return $context
                ->buildViolation('Тип материала отсутствует!')
                ->addViolation();
        }
    }

    /**
     * @param mixed $date
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateAccess($access, ExecutionContextInterface $context)
    {
        if (!in_array($access, $this->materialsServices::ACCESS)) {
            return $context
                ->buildViolation('Тип доступа материала отсутствует!')
                ->addViolation();
        }
    }

    /**
     * @param mixed $date_time
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateStreamStart($stream_start, ExecutionContextInterface $context)
    {
        $form = $context->getObject()->getParent();
        $type = $form->get('type')->getData();
        $is_stream_finished = $form->get('is_stream_finished')->getData();

        if ($stream_start) {
            if ($stream_start->getTimestamp() <= time() && !$is_stream_finished) {
                return $context
                    ->buildViolation('Нельзя устанавливать прошедшую дату!')
                    ->addViolation();
            }
        }

        if ($type == $this->materialsServices::TYPE_STREAM && empty($stream_start) && !$is_stream_finished) {
            return $context
                ->buildViolation('Нужно указать указать дату начала эфира!')
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
        if ($date) {
            $form = $context->getObject()->getParent();
            $material = $form->get('material_id')->getData();

            //проверка только для материалов которые создаются, на старых дату публикации можно выставить
            if (empty($material) && $date->getTimestamp() + 24 * 60 * 60 < time()) {
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
            'allow_extra_fields' => true,
        ]);
    }
}
