<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RefreshTokenRepository::class)
 * @ORM\Table(name="refresh_token", indexes={
 *      @ORM\Index(name="token_validation", columns={"user_id", "expiration_time", "token"}),
 * })
 */
class RefreshToken
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
     * @ORM\Column(type="string", length=64)
     */
    private $token;

    /**
     * @ORM\Column(type="integer")
     */
    private $expiration_time;

    /**
     * @ORM\OneToOne(targetEntity=AuthToken::class, mappedBy="refresh_token", cascade={"persist", "remove"})
     */
    private $authToken;

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

    public function getAuthToken(): ?AuthToken
    {
        return $this->authToken;
    }

    public function setAuthToken(AuthToken $authToken): self
    {
        // set the owning side of the relation if necessary
        if ($authToken->getRefreshToken() !== $this) {
            $authToken->setRefreshToken($this);
        }

        $this->authToken = $authToken;

        return $this;
    }
}
