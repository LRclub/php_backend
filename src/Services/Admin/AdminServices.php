<?php

namespace App\Services\Admin;

use App\Entity\RequestComments;
use App\Entity\User;
use App\Services\HelperServices;
use App\Repository\UserRepository;
use App\Repository\InvoiceRepository;
use App\Repository\FeedbackRepository;
use App\Services\User\FeedbackServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Repository\PromocodesRepository;

class AdminServices
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private InvoiceRepository $invoiceRepository;
    private FeedbackServices $feedbackServices;
    private FeedbackRepository $feedbackRepository;
    private EventDispatcherInterface $eventDispatcher;
    private PromocodesRepository $promocodesRepository;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        InvoiceRepository $invoiceRepository,
        FeedbackServices $feedbackServices,
        FeedbackRepository $feedbackRepository,
        EventDispatcherInterface $eventDispatcher,
        PromocodesRepository $promocodesRepository
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->feedbackServices = $feedbackServices;
        $this->feedbackRepository = $feedbackRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->promocodesRepository = $promocodesRepository;
    }

    /**
     * Получения списка пользователей
     *
     * @param User $user
     * @param FormInterface $form
     * @return User
     */
    public function getUsers(int $page, string $search, array $order_by, int $promocode = 0)
    {
        $result_total_count = 0;
        $result = [];

        // Параметры сортировки
        switch ($order_by['sort_param']) {
            case 'id':
                $order_by['sort_param'] = "u.id";
                break;
            case 'first_name':
                $order_by['sort_param'] = "u.first_name";
                break;
            case 'email':
                $order_by['sort_param'] = "u.email";
                break;
            case 'last_visit':
                $order_by['sort_param'] = "u.last_visit_time";
                break;
            case 'role':
                $order_by['sort_param'] = "u.roles";
                break;
            default:
                $order_by['sort_param'] = "u.id";
                break;
        }

        if (!empty($promocode)) {
            $promocode = $this->promocodesRepository->find($promocode);
        }

        $result_count_all = $this->userRepository->adminFindUsers($page, $promocode, "", $order_by, true);
        $result_total_count = $this->userRepository->adminFindUsers($page, $promocode, $search, $order_by, true);
        $result_users  = $this->userRepository->adminFindUsers($page, $promocode, $search, $order_by, false);

        foreach ($result_users as $user) {
            $user_info = reset($user);
            $promocode = $user['code'];
            // Роль
            $role = 'Пользователь';
            if (in_array('ROLE_EDITOR', $user_info->getRoles())) {
                $role = 'Редактор';
            }
            if (in_array('ROLE_MODERATOR', $user_info->getRoles())) {
                $role = 'Модератор';
            }
            if (in_array('ROLE_ADMIN', $user_info->getRoles())) {
                $role = 'Администратор';
            }

            $result[] = [
                'id' => $user_info->getId(),
                'fio' => $user_info->getFirstName() . ' ' . $user_info->getLastName(),
                'phone' => $user_info->getPhone(),
                'role' => $role,
                'email' => $user_info->getEmail(),
                'last_visit' => date("d.m.Y H:i", $user_info->getLastVisitTime()),
                'promocode' => $promocode
            ];
        }

        return [
            'users' => $result,
            'result_total_count' => $result_total_count,
            'pages' => ceil($result_total_count / $this->userRepository::PAGE_OFFSET),
            'result_count_all' => $result_count_all
        ];
    }

    /**
     * Закрыть обратную связь
     *
     * @param UserInterface $user
     * @param int $feedback_id
     *
     * @return [type]
     */
    public function closeFeedback(UserInterface $user, int $feedback_id, bool $is_admin = false)
    {
        if (!$is_admin) {
            $feedback = $this->feedbackRepository->findOneBy(['id' => $feedback_id, 'user' => $user->getId()]);
        } else {
            $feedback = $this->feedbackRepository->find($feedback_id);
        }

        if (!$feedback || $feedback->getStatus() == $this->feedbackRepository::FEEDBACK_CLOSED) {
            throw new LogicException('Обращение не существует или закрыто');
        }

        $feedback->setStatus($this->feedbackRepository::FEEDBACK_CLOSED);
        $this->em->persist($feedback);
        $this->em->flush();

        return true;
    }

    /**
     * Поиск пользователя для обратной связи
     *
     * @param string $search
     *
     * @return [type]
     */
    public function userSearch(string $search)
    {
        if (empty($search)) {
            return [];
        }

        $result = $this->userRepository->userSearchFeedback($search);
        return $result;
    }
}
