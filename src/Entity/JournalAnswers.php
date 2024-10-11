<?php

namespace App\Entity;

use App\Repository\JournalAnswersRepository;
use App\Services\HelperServices;
use App\Services\Journal\JournalServices;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=JournalAnswersRepository::class)
 */
class JournalAnswers
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="text")
     */
    private $question;

    /**
     * @ORM\Column(type="text")
     */
    private $answer;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $question_type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getQuestionType(): ?string
    {
        return $this->question_type;
    }

    public function setQuestionType(string $question_type): self
    {
        $this->question_type = $question_type;

        return $this;
    }

    /**
     * Формируем пустой ответ для отчета дня
     *
     * @param null $date
     * @param null $questions
     * @param null $id
     * @param array $answers
     * @return array
     */
    public static function getBlankReport($date = null, $questions = null, $id = null, $answers = [])
    {
        if (count($questions) != count($answers)) {
            $answers = array_fill(0, count($questions), '');
        }

        $results = [];
        foreach ($questions as $key => $item) {
            $results[] = [
                'question' => $item,
                'answer' => $answers[$key]
            ];
        }

        return [
            'id' => $id,
            'date' => $date,
            'result' => $results
        ];
    }

    /**
     * Формируем пустой ответ для дневника благодарности
     *
     * @param null $date
     * @param null $question
     * @param null $id
     * @param array $result
     * @return array
     */
    public static function getBlankGratitude($date = null, $question = null, $id = null, $result = [])
    {
        return [
            'id' => $id,
            'date' => $date,
            'question' => $question,
            'result' => $result
        ];
    }

    public function getArrayGratitude()
    {
        $answer = HelperServices::isJson($this->getAnswer()) ? $this->getAnswer() : '[]';
        $answers = json_decode($answer, true);

        return self::getBlankGratitude(
            $this->getDate()->format('Y-m-d'),
            $this->getQuestion(),
            $this->getId(),
            $answers
        );
    }

    public function getArrayReport()
    {
        $question = HelperServices::isJson($this->getQuestion()) ? $this->getQuestion() : '[]';
        $answer = HelperServices::isJson($this->getAnswer()) ? $this->getAnswer() : '[]';

        $questions = json_decode($question, true);
        $answers = json_decode($answer, true);

        return self::getBlankReport($this->getDate()->format('Y-m-d'), $questions, $this->getId(), $answers);
    }
}
