<?php

namespace App\Controller\Journal;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;

class JournalController extends BaseApiController
{
    /**
     * Дневник благодарности
     *
     * @Route("/panel/journal", name="panel_journal", methods={"GET"})
     */
    public function journalAction(
        CoreSecurity $security,
        Request $request
    ): Response {
        $user = $security->getUser();

        return $this->render('/pages/journal/journal.html.twig', [
            'title' => 'Дневник благодарности'
        ]);
    }

    /**
     * Отчет дня (Дневник рефлексии)
     *
     * @Route("/panel/journal/report", name="panel_report_workbook", methods={"GET"})
     */
    public function reportAction(
        CoreSecurity $security,
        Request $request
    ): Response {
        $user = $security->getUser();

        return $this->render('/pages/journal/report.html.twig', [
            'title' => 'Дневник рефлексии'
        ]);
    }

    /**
     * Рабочая тетрадь (дневник эффективности)
     *
     * @Route("/panel/journal/workbook", name="panel_journal_workbook", methods={"GET"})
     */
    public function workbookAction(
        CoreSecurity $security,
        Request $request
    ): Response {
        $user = $security->getUser();

        return $this->render('/pages/journal/workbook.html.twig', [
            'title' => 'Дневник эффективности'
        ]);
    }

    /**
     * Заметки
     *
     * @Route("/panel/journal/notes", name="panel_journal_notes", methods={"GET"})
     */
    public function notesAction(
        CoreSecurity $security,
        Request $request
    ): Response {
        $user = $security->getUser();

        return $this->render('/pages/journal/notes.html.twig', [
            'title' => 'Заметки'
        ]);
    }
}
