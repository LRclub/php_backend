<?php

namespace App\Services\Journal;

// Entity
use App\Entity\JournalAnswers;
use App\Entity\JournalNotes;
use App\Entity\JournalQuestions;
use App\Entity\JournalWorkbook;
use App\Entity\User;
// Repository
use App\Repository\JournalQuestionsRepository;
use App\Repository\JournalAnswersRepository;
use App\Repository\JournalNotesRepository;
use App\Repository\JournalWorkbookRepository;
// Services
use App\Services\DateServices;
// Components
use App\Services\HelperServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Doctrine\ORM\EntityManagerInterface;

class JournalServices
{
    public const JOURNAL_ANSWER_GRATITUDE = 'gratitude';
    public const JOURNAL_ANSWER_REPORT = 'report';

    private EntityManagerInterface $em;
    private CoreSecurity $security;
    private JournalQuestionsRepository $journalQuestionsRepository;
    private JournalAnswersRepository $journalAnswersRepository;
    private JournalNotesRepository $journalNotesRepository;
    private JournalWorkbookRepository $journalWorkbookRepository;
    private DateServices $dateServices;

    public function __construct(
        EntityManagerInterface $em,
        CoreSecurity $security,
        JournalQuestionsRepository $journalQuestionsRepository,
        JournalAnswersRepository $journalAnswersRepository,
        JournalNotesRepository $journalNotesRepository,
        JournalWorkbookRepository $journalWorkbookRepository,
        DateServices $dateServices
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->journalQuestionsRepository = $journalQuestionsRepository;
        $this->journalAnswersRepository = $journalAnswersRepository;
        $this->journalNotesRepository = $journalNotesRepository;
        $this->journalWorkbookRepository = $journalWorkbookRepository;
        $this->dateServices = $dateServices;
    }

    /**
     * Список вопросов
     *
     * @return array
     */
    public function getQuestions(): array
    {
        $questions = $this->journalQuestionsRepository->getQuestions();
        if (!$questions) {
            return [];
        }

        foreach ($questions as $key => $question) {
            if ($question['type'] == self::JOURNAL_ANSWER_REPORT) {
                $questions[$key]['type_ru'] = 'Дневник рефлексии';
            } else {
                $questions[$key]['type_ru'] = 'Дневник благодарности';
            }
        }

        return $questions;
    }

    /**
     * Поиск вопроса по ID
     *
     * @return array
     */
    public function getQuestion(int $id): array
    {
        $question = $this->journalQuestionsRepository->getQuestion($id);
        if (!$question) {
            return [];
        }

        $question = reset($question);

        if ($question['type'] == self::JOURNAL_ANSWER_REPORT) {
            $question['type_ru'] = 'Дневник рефлексии';
        } else {
            $question['type_ru'] = 'Дневник благодарности';
        }

        return $question;
    }

    /**
     * Получение ответов для дневника благодарности или отчета дня
     *
     * @param mixed $user
     * @param mixed $date
     * @param mixed $type
     *
     * @return array
     */
    public function getAnswer(
        User $user,
        string $date,
        string $type
    ): array {
        $date = $this->dateServices->validateDate($date);
        if (!$date) {
            throw new LogicException("Введите корректную дату в формате YYYY-MM-DD");
        }

        if (
            $type != self::JOURNAL_ANSWER_GRATITUDE &&
            $type != self::JOURNAL_ANSWER_REPORT
        ) {
            throw new LogicException("Ошибка типа");
        }

        $result = $this->journalAnswersRepository->findOneBy([
            'user' => $user,
            'question_type' => $type,
            'date' => $date
        ]);

        if (!$result) {
            if ($type == self::JOURNAL_ANSWER_GRATITUDE) {
                return JournalAnswers::getBlankGratitude($date->format('Y-m-d'), $this->getGratitudeQuestion());
            } else {
                return JournalAnswers::getBlankReport($date->format('Y-m-d'), $this->getReportQuestions());
            }
        }

        if ($type == self::JOURNAL_ANSWER_GRATITUDE) {
            return $result->getArrayGratitude();
        } else {
            return $result->getArrayReport();
        }
    }

    /**
     * Сохранение ответов
     *
     * @param User $user
     * @param array $answer
     * @param string $date
     * @param string $type
     *
     * @return JournalAnswers
     */
    public function saveAnswer(User $user, array $answer, string $date, string $type): JournalAnswers
    {
        $date = $this->dateServices->validateDate($date);
        if (!$date) {
            throw new LogicException("Введите корректную дату в формате YYYY-MM-DD");
        }

        if (
            $type != self::JOURNAL_ANSWER_GRATITUDE &&
            $type != self::JOURNAL_ANSWER_REPORT
        ) {
            throw new LogicException("Ошибка типа");
        }

        // Проверяем есть ли уже ответ
        $answer_entity = $this->journalAnswersRepository->findOneBy([
            'question_type' => $type,
            'user' => $user,
            'date' => $date
        ]);

        // Если ответ не найден
        if (!$answer_entity) {
            $question_string = ($type == self::JOURNAL_ANSWER_REPORT)
                ? json_encode($this->getReportQuestions()) : $this->getGratitudeQuestion();

            $answer_entity = new JournalAnswers();
            $answer_entity
                ->setQuestion($question_string)
                ->setUser($user)
                ->setDate($date)
                ->setQuestionType($type);
        }

        $answer_json = json_encode($answer) ?? '[]';

        $answer_entity->setAnswer($answer_json);
        $this->em->persist($answer_entity);
        $this->em->flush();

        return $answer_entity;
    }



    /**
     * Создание заметки
     *
     * @param User $user
     * @param mixed $form
     *
     * @return JournalNotes
     */
    public function createNote(User $user, $form): JournalNotes
    {
        $title = $form->get('title')->getData() ?? "";
        $description = $form->get('description')->getData();

        $note = new JournalNotes();
        $note
            ->setTitle($title)
            ->setDescription($description)
            ->setUser($user)
            ->setCreateTime(time());

        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }

    /**
     * Редактирование заметки
     *
     * @param User $user
     * @param mixed $form
     *
     * @return JournalNotes
     */
    public function editNote(User $user, $form): JournalNotes
    {
        $title = $form->get('title')->getData() ?? "";
        $description = $form->get('description')->getData();
        $note = $form->get('note')->getData();
        $note
            ->setTitle($title)
            ->setDescription($description)
            ->setCreateTime(time());

        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }

    /**
     * Удаление заметки
     *
     * @param User $user
     * @param int $note_id
     *
     * @return [type]
     */
    public function deleteNote(User $user, int $note_id): bool
    {
        $note = $this->journalNotesRepository->findOneBy([
            'id' => $note_id,
            'user' => $user
        ]);

        if (!$note) {
            throw new LogicException("Заметка не найдена");
        }

        if ($note->getIsDeleted()) {
            throw new LogicException("Заметка удалена");
        }

        $note->setIsDeleted(true);
        $this->em->persist($note);
        $this->em->flush();

        return true;
    }

    /**
     * Список заявок
     *
     * @param User $user
     *
     * @return array
     */
    public function getNotes(User $user): array
    {
        $result = [];
        $notes = $this->journalNotesRepository->getNotesArray($user);
        if (!$notes) {
            return $result;
        }

        return $notes;
    }

    /**
     * Сохранение записи в workbook
     *
     * @param User $user
     * @param mixed $form
     *
     * @return JournalWorkbook
     */
    public function saveWorkbook(User $user, $form): JournalWorkbook
    {
        $date = $form->get('date')->getData();
        $result = $form->get('result')->getData();
        $goal = $form->get('goal')->getData();
        $type = $form->get('type')->getData();

        $workbook = $this->journalWorkbookRepository->findUserWorkbook($user, $date, $type);
        if (!$workbook) {
            $workbook = new JournalWorkbook();
            $workbook->setResult($result)->setGoal($goal)->setUser($user)->setDate($date)->setType($type);
        }

        $workbook->setGoal($goal)->setResult($result);
        $this->em->persist($workbook);
        $this->em->flush();

        return $workbook;
    }

    /**
     * Рабочая тетрадь. Данные за год
     *
     * @param User $user
     * @param string $date
     *
     * @return array
     */
    public function getWorkbookYear(User $user, string $date): ?array
    {
        $date = $this->dateServices->validateYear($date);
        if (!$date) {
            return null;
        }

        $workbook = $this->journalWorkbookRepository->findArrayWorkbook($user, $date->format('Y'), 'year');
        if (!$workbook) {
            return null;
        }

        return $workbook->getWorkbookArray();
    }

    /**
     * Рабочая тетрадь. Данные за месяц
     *
     * @param User $user
     * @param string $date
     *
     * @return array
     */
    public function getWorkbookMonth(User $user, string $date): ?array
    {
        $date = $this->dateServices->validateMonth($date);
        if (!$date) {
            return null;
        }

        $workbook = $this->journalWorkbookRepository->findArrayWorkbook($user, $date->format('Y-m'), 'month');
        if (!$workbook) {
            return null;
        }

        return $workbook->getWorkbookArray();
    }

    /**
     * Рабочая тетрадь. Данные за месяц
     *
     * @param User $user
     * @param array $date
     *
     * @return array
     */
    public function getWorkbookWeek(User $user, array $date): ?array
    {
        $this->dateServices->validateWeekRange($date);

        $workbook = $this->journalWorkbookRepository->findWorkbookWeek($user, $date);
        if (!$workbook) {
            return null;
        }

        return $workbook->getWorkbookArray();
    }

    /**
     * Удаление вопроса админа
     *
     * @param int $id
     *
     * @return JournalQuestions
     */
    public function adminDeleteQuestion(int $id): JournalQuestions
    {
        $question = $this->journalQuestionsRepository->findOneBy(['id' => $id]);

        if (empty($question)) {
            throw new LogicException("Вопрос не найден!");
        }

        if ($question->getType() != self::JOURNAL_ANSWER_REPORT) {
            throw new LogicException("Возможно удаление только для " . self::JOURNAL_ANSWER_REPORT . "!");
        }

        if ($question->getIsDeleted()) {
            throw new LogicException("Вопрос уже удален!");
        }

        $this->journalQuestionsRepository->markAsDeleted($question);

        return $question;
    }

    /**
     * Редактирование вопроса админом
     *
     * @param mixed $from
     *
     * @return JournalQuestions
     */
    public function adminEditQuestion($form): JournalQuestions
    {
        $question = $form->get('question')->getData();
        $question_text = $form->get('question_text')->getData();

        $question->setQuestion($question_text);

        if ($question->getType() == self::JOURNAL_ANSWER_REPORT) {
            $sort = $form->get('sort')->getData();
            $question->setSort($sort);
        }

        $this->em->persist($question);
        $this->em->flush();

        return $question;
    }

    /**
     * Добавление вопроса админом
     *
     * @param mixed $from
     *
     * @return JournalQuestions
     */
    public function adminAddQuestion($form): JournalQuestions
    {
        $question = new JournalQuestions();
        $question_text = $form->get('text')->getData();
        $question_sort = $form->get('sort')->getData();

        $question
            ->setType(self::JOURNAL_ANSWER_REPORT)
            ->setSort($question_sort)
            ->setQuestion($question_text);


        $this->em->persist($question);
        $this->em->flush();

        return $question;
    }

    /**
     * Возвращаем вопросы
     * @return array
     */
    private function getReportQuestions()
    {
        $questions = $this->journalQuestionsRepository->getQuestionsByType(self::JOURNAL_ANSWER_REPORT);
        if (empty($questions)) {
            throw new LogicException("Системная ошибка! Данные не найдены!");
        }

        return array_map(function ($item) {
            return $item['question'];
        }, $questions);
    }

    /**
     * Возвращаем текущие вопросы для дневника благодарности
     *
     * @return mixed
     */
    private function getGratitudeQuestion(): string
    {
        $questions = $this->journalQuestionsRepository->getQuestionsByType(self::JOURNAL_ANSWER_GRATITUDE);
        if (empty($questions)) {
            throw new LogicException("Системная ошибка! Вопросы дневника благодарности не найдены!");
        }

        return $questions[0]['question'];
    }
}
