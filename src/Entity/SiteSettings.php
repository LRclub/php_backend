<?php

namespace App\Entity;

use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SiteSettingsRepository::class)
 */
class SiteSettings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Название поля не может быть меньше {{ limit }} символов",
     *      maxMessage = "Название поля не может быть больше {{ limit }} символов",
     *      groups={"Default"}
     * )
     *
     * @Assert\NotBlank(
     *     message = "Нужно указать название поля"
     * )
     *
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Значение не может быть меньше {{ limit }} символов",
     *      maxMessage = "Значение не может быть больше {{ limit }} символов",
     *      groups={"Default"}
     * )
     *
     * @Assert\NotBlank(
     *     message = "Нужно указать название поля"
     * )
     *
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Значение не может быть меньше {{ limit }} символов",
     *      maxMessage = "Значение не может быть больше {{ limit }} символов",
     *      groups={"Default"}
     * )
     *
     * @Assert\NotBlank(
     *     message = "Нужно указать название поля"
     * )
     *
     */
    private $value;

    public $setting_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
