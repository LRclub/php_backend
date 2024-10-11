<?php

namespace App\Form;

use App\Repository\FilesRepository;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FormServices
{
    private CoreSecurity $security;
    private FilesRepository $filesRepository;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        CoreSecurity $security,
        FilesRepository $filesRepository,
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher
    ) {
        $this->security = $security;
        $this->filesRepository = $filesRepository;
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * Проверка доступа к файлу
     *
     * @param mixed $value
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateFileId($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        $form = $context->getObject()->getParent();
        try {
            $user_id = $form->get('user_id')->getData();
            if (!empty($user_id)) {
                $user_find = $this->userRepository->find($user_id);
                if ($user_find) {
                    $user = $user_find;
                }
            }
        } catch (OutOfBoundsException $e) {
        }

        if ($value && $value->getUser()->getId() != $user->getId()) {
            return $context
                ->buildViolation('Данный файл Вам не принадлежит!')
                ->addViolation();
        }
    }

    /**
     * Проверка на наличие доступа к запрашиваемым файлам
     *
     * @param $value
     * @param ExecutionContextInterface $context
     * @return mixed
     */
    public function validateFileIds($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        $form = $context->getObject()->getParent();
        try {
            $user_id = $form->get('user_id')->getData();
            if (!empty($user_id)) {
                $user_find = $this->userRepository->find($user_id);
                if ($user_find) {
                    $user = $user_find;
                }
            }
        } catch (OutOfBoundsException $e) {
        }

        if (is_array($value)) {
            $files_list = $this->filesRepository->findByUserIdAndFileIds($user, $value);

            $result_ids = array_map(function ($item) {
                return $item['id'];
            }, $files_list);

            if (count(array_diff($value, $result_ids)) > 0) {
                return $context
                    ->buildViolation('Ошибка прав доступа к файлам!')
                    ->addViolation();
            }
        }
    }

    /**
     * Проверка совпадает ли введенный пароль с паролем пользователя
     *
     * @param mixed $password
     * @param ExecutionContextInterface $context
     *
     * @return [type]
     */
    public function validateCurrentPassword($password, $user, ExecutionContextInterface $context)
    {
        if (empty($password)) {
            return $context
                ->buildViolation('Нужно указать пароль!')
                ->addViolation();
        }
        if (!$this->userPasswordHasher->isPasswordValid($user, $password)) {
            return $context
                ->buildViolation('Пароль указан неверно!')
                ->addViolation();
        }
    }

    public function validateFIO($value, ExecutionContextInterface $context)
    {
        if (preg_match("/[^a-zа-яё ]/iu", $value)) {
            return $context
                ->buildViolation('Разрешены только буквы латинского и русского алфавита!')
                ->addViolation();
        }
    }
}
