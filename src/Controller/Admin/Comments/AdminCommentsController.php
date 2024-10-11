<?php

namespace App\Controller\Admin\Comments;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\User\FeedbackServices;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\Admin\AdminCommentsServices;

class AdminCommentsController extends BaseApiController
{
    /**
     * Список комментариев
     *
     * @Route("/admin/comments/{status}",
     *          name="admin_comments",
     *          requirements={"status"="(deleted)?"},
     *          methods={"GET"}, defaults={"deleted"=false}
     *        )
     */
    public function getCommentsAdminAction(
        CoreSecurity $security,
        Request $request,
        AdminCommentsServices $adminCommentsServices,
        $status
    ) {
        $user = $security->getUser();

        if (!$user->getIsAdmin() && !$user->getIsModerator()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        // Сортировка
        $search = trim($request->query->get('search'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;
        $is_deleted = intval($status === 'deleted');

        $result = $adminCommentsServices->getComments($page, $order_by, $search, $is_deleted);

        return $this->render('/pages/admin/comments/list.html.twig', [
            'title' => 'Комментарии',
            'search' => $search,
            'page' => $page,
            'pages' => $result['pages'],
            'comments_count' => $result['comments_count'],
            'is_deleted' => $is_deleted,
            'result' => $result['comments'],
        ]);
    }
}
