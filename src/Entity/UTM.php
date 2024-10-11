<?php

namespace App\Entity;

use App\Repository\UTMRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UTMRepository::class)
 */
class UTM
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
    private $utm_source;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $utm_medium;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $utm_campaign;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $utm_term;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $utm_content;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $action;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $User;

    /**
     * @ORM\Column(type="integer")
     */
    private $visit_time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtmSource(): ?string
    {
        return $this->utm_source;
    }

    public function setUtmSource(string $utm_source): self
    {
        $this->utm_source = $utm_source;

        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utm_medium;
    }

    public function setUtmMedium(string $utm_medium): self
    {
        $this->utm_medium = $utm_medium;

        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utm_campaign;
    }

    public function setUtmCampaign(string $utm_campaign): self
    {
        $this->utm_campaign = $utm_campaign;

        return $this;
    }

    public function getUtmTerm(): ?string
    {
        return $this->utm_term;
    }

    public function setUtmTerm(string $utm_term): self
    {
        $this->utm_term = $utm_term;

        return $this;
    }

    public function getUtmContent(): ?string
    {
        return $this->utm_content;
    }

    public function setUtmContent(string $utm_content): self
    {
        $this->utm_content = $utm_content;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

        return $this;
    }

    public function getVisitTime(): ?int
    {
        return $this->visit_time;
    }

    public function setVisitTime(int $visit_time): self
    {
        $this->visit_time = $visit_time;

        return $this;
    }
}
