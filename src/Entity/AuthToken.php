<?php

namespace App\Entity;

use App\Repository\AuthTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AuthTokenRepository::class)
 * @ORM\Table(name="auth_token", indexes={
 *      @ORM\Index(name="token_validation", columns={"user_id", "expiration_time", "token"}),
 * })
 */
class AuthToken
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="authTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $token;

    /**
     * @ORM\Column(type="integer")
     */
    private $expiration_time;

    /**
     * @ORM\OneToOne(targetEntity=RefreshToken::class, inversedBy="authToken", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $refresh_token;

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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getExpirationTime(): ?int
    {
        return $this->expiration_time;
    }

    public function setExpirationTime(int $expiration_time): self
    {
        $this->expiration_time = $expiration_time;

        return $this;
    }

    public function getRefreshToken(): ?RefreshToken
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(RefreshToken $refresh_token): self
    {
        $this->refresh_token = $refresh_token;

        return $this;
    }
}
