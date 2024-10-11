<?php

namespace App\Controller\Admin\Users;

use App\Controller\Base\BaseApiController;
use App\Repository\UserRepository;
use App\Services\Admin\AdminServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AdminUsersController extends BaseApiController
{
    /**
     * Страница пользователей
     *
     * @Route("/admin/users", name="admin_users", methods={"GET"})
     */
    public function usersAction(Request $request, AdminServices $adminServices): Response
    {
        $title = "Список пользователей";
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;
        $search = trim($request->query->get('search'));
        $promocode = intval($request->query->get('promocode'));

        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;

        $result = $adminServices->getUsers($page, $search, $order_by, $promocode);

        if ($promocode && $result) {
            $title = 'Пользователи зарегистрировавшиеся по промокоду "' . reset($result)[0]['promocode'] . '"';
        }

        return $this->render('/pages/admin/users/users.html.twig', [
            'title' => $title,
            'result' => $result['users'],
            'search' => $search,
            'result_total_count' => $result['result_total_count'],
            'pages' => $result['pages'],
            'page' => $page,
            'result_count_all' => $result['result_count_all']
        ]);
    }

    /**
     * Просмотр админом пользователей
     *
     * @Route("/admin/users/view/{id}", name="admin_users_view", methods={"GET"})
     */
    public function usersViewAction(UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->findById($id);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $username = trim($user->getFirstName() . ' ' . $user->getLastName());
        $username = !empty($username) ? '«' . $username . '»' : '';

        return $this->render('/pages/admin/users/view_user.html.twig', [
            'title' => 'Пользователь #' . $user->getId() . ' ' . $username,
            'user_id' => $id
        ]);
    }
}
