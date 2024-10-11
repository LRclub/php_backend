<?php

namespace App\Services\Admin;

// Entity

use App\Entity\Comments;
// Repository
use App\Repository\CommentsRepository;
// Etc
use Doctrine\ORM\EntityManagerInterface;

class AdminCommentsServices
{
    private EntityManagerInterface $em;
    private CommentsRepository $commentsRepository;

    private const PAGE_OFFSET = 20;

    public function __construct(
        EntityManagerInterface $em,
        CommentsRepository $commentsRepository
    ) {
        $this->em = $em;
        $this->commentsRepository = $commentsRepository;
    }

    /**
     * Получить список комментариев
     *
     * @param int $page
     * @param array $order_by
     * @param string $search
     * @param int $is_deleted
     *
     * @return [type]
     */
    public function getComments(
        int $page,
        array $order_by,
        string $search,
        int $is_deleted
    ) {
        $limit = self::PAGE_OFFSET;
        $offset = (intval($page - 1)) * self::PAGE_OFFSET;
        $result = ['pages' => 0, 'comments' => [], 'comments_count' => 0];

        $filter_params = [
            'limit' => $limit,
            'offset' => $offset,
            'sort_param' => $order_by['sort_param'],
            'sort_type' => $order_by['sort_type'],
            'search' => $search
        ];

        $comments = $this->commentsRepository->getAdminComments($is_deleted, $filter_params);
        if (!$comments) {
            return $result;
        }

        foreach ($comments as $comment) {
            $result['comments'][] = $comment->getAdminArrayData();
        }
        $filter_params['offset'] = 0;
        $result['comments_count'] = (int)$this->commentsRepository->getAdminComments($is_deleted, $filter_params, true);
        $result['pages'] = round($result['comments_count'] / self::PAGE_OFFSET);

        return $result;
    }
}
