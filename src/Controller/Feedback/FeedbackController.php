<?php

namespace App\Controller\Feedback;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\User\FeedbackServices;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeedbackController extends BaseApiController
{
    /**
     * Обратная связь
     *
     * @Route("/panel/feedback/{status}",
     *      name="panel_feedback",
     *      requirements={"status"="(closed)?"},
     *      methods={"GET"},
     *      defaults={"closed"=false})
     */
    public function feedbackAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        $status
    ): Response {
        $user = $security->getUser();

        if ($user->getIsAdmin()) {
            return $this->redirect('/admin/feedback');
        }

        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;

        $closed = intval($status === 'closed');
        $result = $feedbackServices->getFeedbackRequests($user, $closed, $page);

        return $this->render('/pages/feedback/requests.html.twig', [
            'title' => 'Поддержка сайта',
            'offset' => $result['offset'],
            'page' => $page,
            'pages' => $result['pages'],
            'closed' => $closed,
            'result_closed_count' => $result['result_closed_count'],
            'result_opened_count' => $result['result_opened_count'],
            'result_total_count' => $result['result_total_count'],
            'result' => $result['result'],
        ]);
    }

    /**
     * Просмотр заявки обратной связи
     *
     * @Route("/panel/feedback/{id}", requirements={"id"="\d+"}, name="panel_feedback_view", methods={"GET"})
     */
    public function feedbackViewAction(CoreSecurity $security, FeedbackServices $feedbackServices, $id): Response
    {
        $user = $security->getUser();
        $result = $feedbackServices->getFeedback($user, $id);

        if (!$result) {
            throw new NotFoundHttpException();
        }
        return $this->render('/pages/feedback/view.html.twig', [
            'feedback_id' => $id,
            'closed' => $result['feedbackInfo']['status'],
            'title' => (isset($result['feedbackInfo'])) ? $result['feedbackInfo']['title'] : null,
            'result' => $result
        ]);
    }

    /**
     * Создать обращение
     *
     * @Route("/panel/feedback/create", name="panel_feedback_create", methods={"GET"})
     */
    public function feedbackEditAction(): Response
    {
        return $this->render('/pages/feedback/create.html.twig', [
            'title' => 'Профиль пользователя'
        ]);
    }
}
