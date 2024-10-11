<?php

namespace App\Controller\Admin\Feedback;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\User\FeedbackServices;
use App\Services\Messages\MessagesServices;

class AdminFeedbackController extends BaseApiController
{
    /**
     * Обратная связь все заявки
     *
     * @Route("/admin/feedback/{status}",
     *          name="admin_feedback",
     *          requirements={"status"="(closed)?"},
     *          methods={"GET"}, defaults={"closed"=false}
     *        )
     */
    public function feedbackAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        $status
    ): Response {
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;
        $search = trim($request->query->get('search'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;
        $closed = intval($status === 'closed');

        $user = $security->getUser();
        $result = $feedbackServices->getFeedbackRequests($user, $closed, $page, $order_by, $search);

        return $this->render('/pages/admin/feedback/feedback.html.twig', [
            'title' => 'Обратная связь',
            'offset' => $result['offset'],
            'page' => $page,
            'pages' => $result['pages'],
            'closed' => $closed,
            'search' => $search,
            'result_closed_count' => $result['result_closed_count'],
            'result_opened_count' => $result['result_opened_count'],
            'result_total_count' => $result['result_total_count'],
            'result' => $result['result'],
        ]);
    }

    /**
     * Просмотр заявки обратной связи
     *
     * @Route("/admin/feedback/{id}", requirements={"id"="\d+"}, name="admin_feedback_view", methods={"GET"})
     */
    public function feedbackView(
        CoreSecurity $security,
        FeedbackServices $feedbackServices,
        int $id
    ): Response {
        $user = $security->getUser();
        $result = $feedbackServices->getFeedback($user, $id);

        if (!$result) {
            throw new NotFoundHttpException();
        }

        return $this->render('/pages/admin/feedback/feedback_view.html.twig', [
            'feedback_id' => $id,
            'closed' => $result['feedbackInfo']['status'],
            'title' => (isset($result['feedbackInfo'])) ? $result['feedbackInfo']['title'] : null,
            'result' => $result
        ]);
    }
}
