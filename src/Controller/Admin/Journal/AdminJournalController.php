<?php

namespace App\Controller\Admin\Journal;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Journal\JournalServices;

class AdminJournalController extends BaseApiController
{
    /**
     * Список вопросов для журнала
     *
     * @Route("/admin/journal", name="admin_journal", methods={"GET"})
     *
     */
    public function journalAction(
        JournalServices $journalServices
    ): Response {
        $questions = $journalServices->getQuestions();

        return $this->render('/pages/admin/journal/list.html.twig', [
            'title' => 'Список вопросов для дневника',
            'questions' => $questions
        ]);
    }

    /**
     * Просмотр вопроса
     *
     * @Route("/admin/journal/{id}", requirements={"id"="\d+"}, name="admin_journal_view", methods={"GET"})
     *
     */
    public function journalViewAction(
        JournalServices $journalServices,
        $id
    ): Response {
        $result = $journalServices->getQuestion($id);
        if (!$result) {
            throw new NotFoundHttpException();
        }

        return $this->render('/pages/admin/journal/view.html.twig', [
            'title' => 'Список вопросов для дневника',
            'result' => $result
        ]);
    }

    /**
     * Создание вопроса
     *
     * @Route("/admin/journal/create", name="admin_journal_create", methods={"GET"})
     *
     */
    public function journalCreateAction(): Response
    {
        return $this->render('/pages/admin/journal/create.html.twig', [
            'title' => 'Новый вопрос для дневника',
        ]);
    }
}
