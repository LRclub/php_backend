<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\PropertyFormType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Repository\SeoRepository;

/**
 * @ORM\Entity(repositoryClass=SeoRepository::class)
 */
class Seo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(
     *      min = 1,
     *      max = 255,
     *      minMessage = "Ссылка не может быть меньше {{ limit }} символа",
     *      maxMessage = "Ссылка не может быть больше {{ limit }} символов",
     *      groups={"Default"}
     * )
     *
     * @Assert\NotBlank(
     *     message = "Нужно указать ссылку на страницу"
     * )
     */
    private $link;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Заголовок не может быть меньше {{ limit }} символов",
     *      maxMessage = "Заголовок не может быть больше {{ limit }} символов",
     *      groups={"Default"}
     * )
     *
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $keywords;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $property = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(
     *      min = 1,
     *      max = 255,
     *      minMessage = "Заголовок страницы не может быть меньше {{ limit }} символа",
     *      maxMessage = "Заголовок страницы не может быть больше {{ limit }} символов",
     *      groups={"Default"}
     * )
     *
     */
    private $title_page;

    public $seo_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getProperty(): ?array
    {
        return $this->property;
    }

    public function setProperty(?array $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getTitlePage(): ?string
    {
        return $this->title_page;
    }

    public function setTitlePage(?string $title_page): self
    {
        $this->title_page = $title_page;

        return $this;
    }
}
