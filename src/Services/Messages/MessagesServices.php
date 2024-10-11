<?php

namespace App\Services\Messages;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Repository\FeedbackMessageRepository;
use App\Repository\FeedbackRepository;

class MessagesServices
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private FeedbackMessageRepository $feedbackMessageRepository;
    private FeedbackRepository $feedbackRepository;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        FeedbackMessageRepository $feedbackMessageRepository,
        FeedbackRepository $feedbackRepository
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->feedbackMessageRepository = $feedbackMessageRepository;
        $this->feedbackRepository = $feedbackRepository;
    }

    /**
     * Отметить сообщение прочитанным в заявке
     *
     *
     * @return [type]
     */
    public function setIsReadMessageFeedback($feedback_id, UserInterface $user)
    {
        $feedback = $this->feedbackRepository->find($feedback_id);
        if ($feedback) {
            $messages = $this->feedbackMessageRepository->findUnreadRequestMessages($feedback->getId(), $user->getId());
            foreach ($messages as $message) {
                $message->setIsRead(true);
                $this->em->persist($message);
                $this->em->flush();
            }
        }
    }
}
