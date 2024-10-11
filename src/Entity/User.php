<?php

namespace App\Entity;

use App\Services\TwigServices;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Ignore;
use App\Repository\UserRepository;
use App\Services\User\UserServices;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity("phone", message="Такой телефон уже существует")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Роль пользователя
    public const ROLE_USER = 'user';

    // Роль админа
    public const ROLE_ADMIN = 'admin';

    // Роль модератора
    public const ROLE_MODERATOR = 'moderator';

    // Роль редактора
    public const ROLE_EDITOR = 'editor';

    // Доступные роли и их ассоциации
    public const AVAILABLE_ROLES = [
        self::ROLE_USER => 'ROLE_USER',
        self::ROLE_ADMIN => 'ROLE_ADMIN',
        self::ROLE_MODERATOR => 'ROLE_MODERATOR',
        self::ROLE_EDITOR => 'ROLE_EDITOR'
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(
     *     message = "Телефон не может быть пустым", groups={"Default", "CreateUser"}
     * )
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @Assert\NotBlank(
     *     message = "E-mail не может быть пустым", groups={"InitUser", "Default", "CreateUser"}
     * )
     * @Assert\Email(
     *     message = "Введите корректный e-mail", groups={"InitUser", "Default", "CreateUser"}
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     *
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity=AuthToken::class, mappedBy="user", orphanRemoval=true)
     * @Ignore()
     */
    private $authTokens;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_confirmed;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_blocked;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="integer")
     */
    private $last_visit_time;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $invited;

    /**
     * @ORM\OneToMany(targetEntity=Promocodes::class, mappedBy="owner")
     */
    private $promocodes;

    /**
     * @ORM\OneToMany(targetEntity=Notice::class, mappedBy="user")
     */
    private $notice;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\NotBlank(
     *     message = "Нужно указать имя", groups={"InitUser", "Default"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Имя должно содержать минимум {{ limit }} символа",
     *      maxMessage = "Имя не должно быть длиннее {{ limit }} символов",
     *      groups={"InitUser", "Default", "CreateUser"}
     * )
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\NotBlank(
     *     message = "Нужно указать фамилию", groups={"InitUser", "Default"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Фамилия должна содержать минимум {{ limit }} символа",
     *      maxMessage = "Фамилия не должна быть длиннее {{ limit }} символов",
     *      groups={"InitUser", "Default", "CreateUser"}
     * )
     */
    private $last_name;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\Length(
     *      min = 2,
     *      max = 60,
     *      minMessage = "Отчество должно содержать минимум {{ limit }} символа",
     *      maxMessage = "Отчество не должно быть длиннее {{ limit }} символов",
     *      groups={"InitUser", "Default", "CreateUser"}
     * )
     */
    private $patronymic_name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice(choices={"male", "female"}, message="Выберите male или female", groups={"Default"})
     */
    private $gender;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthday;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity=Feedback::class, mappedBy="user")
     */
    private $feedback;

    /**
     * @ORM\OneToMany(targetEntity=Invoice::class, mappedBy="user")
     */
    private $invoices;

    private bool $is_logged_admin = false;

    private bool $is_first_month = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_promocode_active;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $subscription_end_date;

    /**
     * @ORM\OneToMany(targetEntity=SubscriptionHistory::class, mappedBy="user")
     */
    private $subscriptionHistories;
    /**
     * @ORM\OneToMany(targetEntity=MaterialsFavorites::class, mappedBy="user", orphanRemoval=true)
     */
    private $materials_favorites;

    /**
     * @ORM\OneToMany(targetEntity=MaterialsCategoriesFavorites::class, mappedBy="user", orphanRemoval=true)
     */
    private $materials_categories_favorites;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="user", orphanRemoval=true)
     */
    private $tasks;

    /**
     * @ORM\OneToMany(targetEntity=Tracker::class, mappedBy="user")
     */
    private $trackers;
    /**
     * @ORM\OneToMany(targetEntity=JournalNotes::class, mappedBy="user", orphanRemoval=true)
     */
    private $notes;

    /**
     * @ORM\OneToOne(targetEntity=Notifications::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $notifications;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $interests;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $super_power;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $principles;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vk;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telegram;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $instagram;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ok;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\ManyToOne(targetEntity=Countries::class)
     */
    private $country;

    public function __construct()
    {
        $this->authTokens = new ArrayCollection();
        $this->promocodes = new ArrayCollection();
        $this->feedback = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->notice = new ArrayCollection();
        $this->subscriptionHistories = new ArrayCollection();
        $this->materials_favorites = new ArrayCollection();
        $this->materials_categories_favorites = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->trackers = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->phone;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->phone;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|AuthToken[]
     */
    public function getAuthTokens(): Collection
    {
        return $this->authTokens;
    }

    public function addAuthToken(AuthToken $authToken): self
    {
        if (!$this->authTokens->contains($authToken)) {
            $this->authTokens[] = $authToken;
            $authToken->setUser($this);
        }

        return $this;
    }

    public function removeAuthToken(AuthToken $authToken): self
    {
        if ($this->authTokens->removeElement($authToken)) {
            // set the owning side to null (unless already changed)
            if ($authToken->getUser() === $this) {
                $authToken->setUser(null);
            }
        }

        return $this;
    }

    public function getIsConfirmed(): ?bool
    {
        return $this->is_confirmed;
    }

    public function setIsConfirmed(bool $is_confirmed): self
    {
        $this->is_confirmed = $is_confirmed;

        return $this;
    }

    public function getIsBlocked(): ?bool
    {
        return $this->is_blocked;
    }

    public function setIsBlocked(bool $is_blocked): self
    {
        $this->is_blocked = $is_blocked;

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

    public function getLastVisitTime(): ?int
    {
        return $this->last_visit_time;
    }

    public function setLastVisitTime(int $last_visit_time): self
    {
        $this->last_visit_time = $last_visit_time;

        return $this;
    }

    public function getInvited(): ?self
    {
        return $this->invited;
    }

    public function setInvited(?self $invited): self
    {
        $this->invited = $invited;

        return $this;
    }

    /**
     * @return Collection|Promocodes[]
     */
    public function getPromocodes(): Collection
    {
        return $this->promocodes;
    }

    public function addPromocode(Promocodes $promocode): self
    {
        if (!$this->promocodes->contains($promocode)) {
            $this->promocodes[] = $promocode;
            $promocode->setOwner($this);
        }

        return $this;
    }

    public function removePromocode(Promocodes $promocode): self
    {
        if ($this->promocodes->removeElement($promocode)) {
            // set the owning side to null (unless already changed)
            if ($promocode->getOwner() === $this) {
                $promocode->setOwner(null);
            }
        }

        return $this;
    }


    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getPatronymicName(): ?string
    {
        return $this->patronymic_name;
    }

    public function setPatronymicName(?string $patronymic_name): self
    {
        $this->patronymic_name = $patronymic_name;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Возвращаем, является ли пользователь админ
     *
     * @return bool
     */
    public function getIsAdmin(): bool
    {
        $admin_code = self::AVAILABLE_ROLES[self::ROLE_ADMIN];

        return in_array(
            $admin_code,
            $this->roles
        );
    }

    /**
     * Возвращаем, является ли пользователь модератор
     *
     * @return bool
     */
    public function getIsModerator(): bool
    {
        $moderator_code = self::AVAILABLE_ROLES[self::ROLE_MODERATOR];

        return in_array(
            $moderator_code,
            $this->roles
        );
    }

    /**
     * Возвращаем, является ли пользователь редактором
     *
     * @return bool
     */
    public function getIsEditor(): bool
    {
        $editor_code = self::AVAILABLE_ROLES[self::ROLE_EDITOR];

        return in_array(
            $editor_code,
            $this->roles
        );
    }

    /**
     * Проверка имеет ли юзер супер права
     *
     * @return bool
     */
    public function getIsSpecialRole(): bool
    {
        if ($this->getIsEditor() || $this->getIsAdmin() || $this->getIsModerator()) {
            return true;
        }

        return false;
    }

    /**
     * Информация о пользователе для инвайтов
     *
     * @return array
     */
    public function userInviteStatus(): array
    {
        $status = $this->isInviteActive();
        $reason = 'Пользователь активирован';

        if (!$this->is_confirmed) {
            $reason = 'Необходимо подтвердить e-mail';
        }

        if ($this->is_blocked) {
            $reason = 'Пользователь заблокирован';
        }

        return [
            'status' => $status,
            'reason' => $reason
        ];
    }

    /**
     * Возвращаем активен пользователь или нет
     *
     * @return bool
     */
    public function isInviteActive(): bool
    {
        return ($this->is_confirmed && !$this->is_blocked);
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection|Feedback[]
     */
    public function getFeedback(): Collection
    {
        return $this->feedback;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->feedback->contains($feedback)) {
            $this->feedback[] = $feedback;
            $feedback->setUser($this);
        }

        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        if ($this->feedback->removeElement($feedback)) {
            // set the owning side to null (unless already changed)
            if ($feedback->getUser() === $this) {
                $feedback->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Invoice[]
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setUser($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getUser() === $this) {
                $invoice->setUser(null);
            }
        }

        return $this;
    }

    public function setIsLoggedAdmin(bool $is_logged_admin): self
    {
        $this->is_logged_admin = $is_logged_admin;

        return $this;
    }

    public function getIsLoggedAdmin(): bool
    {
        return $this->is_logged_admin;
    }

    /**
     * Проверка. Зарегистрирован ли юзер больше месяца
     *
     * @return bool
     */
    public function getIsFisrtMonth(): bool
    {
        if (strtotime("+1 month", $this->create_time) <= time()) {
            return false;
        }

        return true;
    }

    public function getIsEmptyProfile(): bool
    {
        if (empty($this->first_name) || empty($this->last_name) || empty($this->email)) {
            return true;
        }

        return false;
    }

    public function getIsPromocodeActive(): ?bool
    {
        return $this->is_promocode_active;
    }

    public function setIsPromocodeActive(?bool $is_promocode_active): self
    {
        $this->is_promocode_active = $is_promocode_active;

        return $this;
    }

    public function getNotice(): Collection
    {
        return $this->notice;
    }

    public function getNotifications(): ?Notifications
    {
        return $this->notifications;
    }

    public function setNotifications(Notifications $notifications): self
    {
        // set the owning side of the relation if necessary
        if ($notifications->getUser() !== $this) {
            $notifications->setUser($this);
        }

        $this->notifications = $notifications;

        return $this;
    }

    public function getSubscriptionEndDate(): ?int
    {
        return $this->subscription_end_date;
    }

    public function setSubscriptionEndDate(?int $subscription_end_date): self
    {
        $this->subscription_end_date = $subscription_end_date;

        return $this;
    }

    /**
     * @return Collection|SubscriptionHistory[]
     */
    public function getSubscriptionHistories(): Collection
    {
        return $this->subscriptionHistories;
    }

    public function addSubscriptionHistory(SubscriptionHistory $subscriptionHistory): self
    {
        if (!$this->subscriptionHistories->contains($subscriptionHistory)) {
            $this->subscriptionHistories[] = $subscriptionHistory;
            $subscriptionHistory->setUser($this);
        }

        return $this;
    }

    public function removeSubscriptionHistory(SubscriptionHistory $subscriptionHistory): self
    {
        if ($this->subscriptionHistories->removeElement($subscriptionHistory)) {
            // set the owning side to null (unless already changed)
            if ($subscriptionHistory->getUser() === $this) {
                $subscriptionHistory->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaterialsFavorites>
     */
    public function getMaterialsFavorites(): Collection
    {
        return $this->materials_favorites;
    }

    public function addMaterialsFavorite(MaterialsFavorites $materialsFavorite): self
    {
        if (!$this->materials_favorites->contains($materialsFavorite)) {
            $this->materials_favorites[] = $materialsFavorite;
            $materialsFavorite->setUser($this);
        }

        return $this;
    }

    public function removeMaterialsFavorite(MaterialsFavorites $materialsFavorite): self
    {
        if ($this->materials_favorites->removeElement($materialsFavorite)) {
            // set the owning side to null (unless already changed)
            if ($materialsFavorite->getUser() === $this) {
                $materialsFavorite->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaterialsCategoriesFavorites>
     */
    public function getMaterialsCategoriesFavorites(): Collection
    {
        return $this->materials_categories_favorites;
    }

    public function addMaterialsCategoriesFavorite(MaterialsCategoriesFavorites $materialsCategoriesFavorite): self
    {
        if (!$this->materials_categories_favorites->contains($materialsCategoriesFavorite)) {
            $this->materials_categories_favorites[] = $materialsCategoriesFavorite;
            $materialsCategoriesFavorite->setUser($this);
        }

        return $this;
    }

    public function removeMaterialsCategoriesFavorite(MaterialsCategoriesFavorites $materialsCategoriesFavorite): self
    {
        if ($this->materials_categories_favorites->removeElement($materialsCategoriesFavorite)) {
            // set the owning side to null (unless already changed)
            if ($materialsCategoriesFavorite->getUser() === $this) {
                $materialsCategoriesFavorite->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tasks>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Tasks $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setUser($this);
        }

        return $this;
    }

    public function removeTask(Tasks $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getUser() === $this) {
                $task->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tracker>
     */
    public function getTrackers(): Collection
    {
        return $this->trackers;
    }

    public function addTracker(Tracker $tracker): self
    {
        if (!$this->trackers->contains($tracker)) {
            $this->trackers[] = $tracker;
            $tracker->setUser($this);
        }

        return $this;
    }

    public function removeTracker(Tracker $tracker): self
    {
        if ($this->trackers->removeElement($tracker)) {
            // set the owning side to null (unless already changed)
            if ($tracker->getUser() === $this) {
                $tracker->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JournalNotes>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(JournalNotes $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setUser($this);
        }

        return $this;
    }

    public function removeNote(JournalNotes $note): self
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getUser() === $this) {
                $note->setUser(null);
            }
        }

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

    public function getInterests(): ?string
    {
        return $this->interests;
    }

    public function setInterests(?string $interests): self
    {
        $this->interests = $interests;

        return $this;
    }


    /**
     * Данные пользователей для профиля
     *
     * Метод для ПУБЛИЧНОГО предоставления данных!
     * Отдает данные по другим пользователям по запросу.
     * Конфиденциальную информацию здесь не отдавать!
     *
     * @return array
     */
    public function getUserProfileArrayData(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'patronymic_name' => $this->patronymic_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'interests' => $this->interests,
            'super_power' => $this->super_power,
            'principles' => $this->principles,
            'location' => [
              'country' => $this->getCountry() ? $this->getCountry()->getArray() : null,
              'city' => $this->getCity()
            ],
            'is_empty_profile' => $this->getIsEmptyProfile(),
            'last_visit_time' => $this->last_visit_time,
            'last_visit_time_formatted' => $this->getOnlineTime($this),
            'is_admin' => $this->getIsAdmin(),
            'is_moderator' => $this->getIsModerator(),
            'is_editor' => $this->getIsEditor(),
            'networks' => [
                'ok' => $this->getOk(),
                'tg' => $this->getTelegram(),
                'vk' => $this->getVk(),
                'inst' => $this->getInstagram()
            ]
        ];
    }

    /**
     * Возвращает в текстовом виде последний онлайн пользователя
     *
     * @param UserInterface $user
     *
     * @return [type]
     */
    public function getOnlineTime(UserInterface $user)
    {
        if (empty($user->getId())) {
            return false;
        }

        $last_visit = $user->getLastVisitTime();
        $was_online = ($user->getGender() == 'female') ? "Была в сети " : "Был в сети ";

        $diff = time() - $last_visit;
        if ($diff < 60 * 5) {
            return 'Онлайн';
        } elseif ($diff > 0) {
            $day_diff = floor($diff / 86400);
            if ($day_diff == 0) {
                if ($diff < 3600) {
                    return $was_online . TwigServices::plural(floor($diff / 60), ['минуту', 'минуты', 'минут'])
                        . ' назад';
                }
                if ($diff < 7200) {
                    return $was_online . 'час назад';
                }
                if ($diff < 86400) {
                    return $was_online . TwigServices::plural(floor($diff / 3600), ['час', 'часа', 'часов'])
                        . ' назад';
                }
            }
            if ($day_diff == 1) {
                return $was_online . 'вчера в ' . date("H:i", $last_visit);
            }
            if ($day_diff < 7) {
                return $was_online . TwigServices::plural($day_diff, ['день', 'дня', 'дней']) . ' назад';
            }
            if ($day_diff < 31) {
                return $was_online . TwigServices::plural(ceil($day_diff / 7), ['неделю', 'недели', 'недель'])
                    . ' назад';
            }
            if ($day_diff < 60) {
                return $was_online . 'больше месяца назад';
            }
            return 'Пользователь давно не заходил';
        } else {
            return $was_online . gmdate("Y-m-d", $last_visit) . ' в ' . date("H:i", $last_visit);
        }
    }

    public function getSuperPower(): ?string
    {
        return $this->super_power;
    }

    public function setSuperPower(?string $super_power): self
    {
        $this->super_power = $super_power;

        return $this;
    }

    public function getPrinciples(): ?string
    {
        return $this->principles;
    }

    public function setPrinciples(?string $principles): self
    {
        $this->principles = $principles;

        return $this;
    }

    public function getVk(): ?string
    {
        return $this->vk;
    }

    public function setVk(?string $vk): self
    {
        $this->vk = $vk;

        return $this;
    }

    public function getTelegram(): ?string
    {
        return $this->telegram;
    }

    public function setTelegram(?string $telegram): self
    {
        $this->telegram = $telegram;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getOk(): ?string
    {
        return $this->ok;
    }

    public function setOk(?string $ok): self
    {
        $this->ok = $ok;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?Countries
    {
        return $this->country;
    }

    public function setCountry(?Countries $country): self
    {
        $this->country = $country;

        return $this;
    }
}
