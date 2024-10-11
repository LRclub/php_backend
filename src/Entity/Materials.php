<?php

namespace App\Entity;

use App\Repository\MaterialsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\NamedNativeQuery as NamedNativeQuery;

/**
 * @ORM\Entity(repositoryClass=MaterialsRepository::class)
 */
class Materials
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity=CommentsCollector::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $comments_collector;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $views_count = 0;

    /**
     * @ORM\ManyToOne(targetEntity=LikesCollector::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $likes_collector;
    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $update_time;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $short_description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lazy_post;

    /**
     * @ORM\Column(type="smallint")
     */
    private $access = 1;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_show_bill = 0;
    /**
     * @ORM\ManyToOne(targetEntity=MaterialsCategories::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $stream;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stream_start;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_stream_finished = 0;
    /**
     * @ORM\ManyToOne(targetEntity=Files::class)
     */
    private $audio;
    /**
     * @ORM\ManyToOne(targetEntity=Files::class)
     */
    private $video;
    /**
     * @ORM\ManyToOne(targetEntity=Files::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $preview_image;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stream_event_id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_notification_sended = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $stream_notification_sended = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCommentsCollector(): ?CommentsCollector
    {
        return $this->comments_collector;
    }

    public function setCommentsCollector(?CommentsCollector $comments_collector): self
    {
        $this->comments_collector = $comments_collector;

        return $this;
    }

    public function getViewsCount(): ?int
    {
        return $this->views_count;
    }

    public function setViewsCount(?int $views_count): self
    {
        $this->views_count = $views_count;

        return $this;
    }


    public function getLikesCollector(): ?LikesCollector
    {
        return $this->likes_collector;
    }

    public function setLikesCollector(?LikesCollector $likes_collector): self
    {
        $this->likes_collector = $likes_collector;
        return $this;
    }

    public function getCreateTime(): ?int
    {
        return $this->create_time;
    }

    public function setCreateTime(int $create_time): self
    {
        $this->create_time = $create_time;
        return $this;
    }

    public function getUpdateTime(): ?int
    {
        return $this->update_time;
    }

    public function setUpdateTime(?int $update_time): self
    {
        $this->update_time = $update_time;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->short_description;
    }

    public function setShortDescription(?string $short_description): self
    {
        $this->short_description = $short_description;

        return $this;
    }

    public function getPreviewImage(): ?Files
    {
        return $this->preview_image;
    }

    public function setPreviewImage(?Files $preview_image): self
    {
        $this->preview_image = $preview_image;

        return $this;
    }

    public function getLazyPost(): ?int
    {
        return $this->lazy_post;
    }

    public function setLazyPost(?int $lazy_post): self
    {
        $this->lazy_post = $lazy_post;

        return $this;
    }

    public function getAccess(): ?int
    {
        return $this->access;
    }

    public function setAccess(int $access): self
    {
        $this->access = $access;

        return $this;
    }

    public function getIsShowBill(): ?bool
    {
        return $this->is_show_bill;
    }

    public function setIsShowBill(?bool $is_show_bill): self
    {
        $this->is_show_bill = $is_show_bill;

        return $this;
    }


    public function getCategory(): ?MaterialsCategories
    {
        return $this->category;
    }

    public function setCategory(?MaterialsCategories $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStream(): ?string
    {
        return $this->stream;
    }

    public function setStream(?string $stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    public function getStreamStart(): ?int
    {
        return $this->stream_start;
    }

    public function setStreamStart(?int $stream_start): self
    {
        $this->stream_start = $stream_start;

        return $this;
    }

    public function getIsStreamFinished(): ?bool
    {
        return $this->is_stream_finished;
    }

    public function setIsStreamFinished(?bool $is_stream_finished): self
    {
        $this->is_stream_finished = $is_stream_finished;

        return $this;
    }

    public function getAudio(): ?Files
    {
        return $this->audio;
    }

    public function setAudio(?Files $audio): self
    {
        $this->audio = $audio;
        return $this;
    }

    public function getVideo(): ?Files
    {
        return $this->video;
    }

    public function setVideo(?Files $video): self
    {
        $this->video = $video;
        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(?bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public function getStreamEventId(): ?int
    {
        return $this->stream_event_id;
    }

    public function setStreamEventId(?int $stream_event_id): self
    {
        $this->stream_event_id = $stream_event_id;
        return $this;
    }

    public function getIsNotificationSended(): ?bool
    {
        return $this->is_notification_sended;
    }

    public function setIsNotificationSended(?bool $is_notification_sended): self
    {
        $this->is_notification_sended = $is_notification_sended;

        return $this;
    }

    public function getStreamNotificationSended(): ?bool
    {
        return $this->stream_notification_sended;
    }

    public function setStreamNotificationSended(?bool $stream_notification_sended): self
    {
        $this->stream_notification_sended = $stream_notification_sended;

        return $this;
    }
}
