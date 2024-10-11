<?php

namespace App\Controller\Api\Journal;

use App\Controller\Base\BaseApiController;
use App\Services\Messages\MessagesServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\Admin\AdminServices;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use App\Services\Journal\JournalServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Form\Journal\JournalNotesType;

class JournalNotesController extends BaseApiController
{
    /**
     * Создать заметку
     *
     * @Route("/api/journal/note", name="api_journal_note_create", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="title", type="string", description="Заголовок", example="Заметка 1"),
     *       @OA\Property(property="description", type="string", description="Описание", example="Купить еду"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Заметка успешно сохранена")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="journal notes")
     * @Security(name="Bearer")
     */
    public function journalNoteCreateAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ): Response {
        $user = $security->getUser();

        $note = [
            'title' => trim($this->getJson($request, 'title')),
            'description' => trim($this->getJson($request, 'description')),
            'note' => null
        ];

        $form = $this->createFormByArray(JournalNotesType::class, $note);
        if ($form->isValid()) {
            $note = $journalServices->createNote($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => $note->getArrayNoteData()]);
    }

    /**
     * Редактировать заметку
     *
     * @Route("/api/journal/note", name="api_journal_note_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="note_id", type="integer", description="ID заметки", example="1"),
     *       @OA\Property(property="title", type="string", description="Заголовок", example="Заметка 1"),
     *       @OA\Property(property="description", type="string", description="Описание", example="Купить еду"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Заметка успешно изменена")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="journal notes")
     * @Security(name="Bearer")
     */
    public function journalNoteEditAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ): Response {
        $user = $security->getUser();

        $note = [
            'title' => trim($this->getJson($request, 'title')),
            'description' => trim($this->getJson($request, 'description')),
            'note' => $this->getIntOrNull($this->getJson($request, 'note_id')) ?? 0
        ];

        $form = $this->createFormByArray(JournalNotesType::class, $note);
        if ($form->isValid()) {
            $note = $journalServices->editNote($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => "Заметка успешно изменена"]);
    }

    /**
     * Удалить заметку
     *
     * @Route("/api/journal/note/{id}", requirements={"id"="\d+"}, name="api_journal_note_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID заметки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Заметка успешно удалена")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Заметка не найдена")
     *
     * @OA\Tag(name="journal notes")
     * @Security(name="Bearer")
     */
    public function journalNoteDeleteAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices,
        int $id
    ): Response {
        $user = $security->getUser();

        try {
            $journalServices->deleteNote($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 404);
        }

        return $this->jsonSuccess(['result' => "Заметка успешно удалена"]);
    }

    /**
     * Список заметок пользователя
     *
     * @Route(path="/api/journal/note", name="api_journal_note_list", methods={"GET"})
     *
     * @OA\Response(response=200, description="Заметки успешно получены")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="journal notes")
     * @Security(name="Bearer")
     */
    public function journalNotesAction(
        CoreSecurity $security,
        JournalServices $journalServices
    ) {
        $user = $security->getUser();
        $notes = $journalServices->getNotes($user);

        return $this->jsonSuccess(['result' => $notes]);
    }
}
