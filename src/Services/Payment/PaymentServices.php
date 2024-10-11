<?php

namespace App\Services\Payment;

// Entity
use App\Entity\User;
use App\Entity\Invoice;
use App\Entity\SubscriptionHistory;
use App\Entity\SpecialistsRequests;
use App\Entity\Recosting;
use App\Entity\Tariffs;
// Repository
use App\Repository\InvoiceRepository;
use App\Repository\TariffsRepository;
use App\Repository\RecostingRepository;
use App\Repository\SpecialistsRequestsRepository;
use App\Repository\SiteSettingsRepository;
// Services
use App\Services\Payment\SubscriptionHistoryServices;
use App\Services\Event\UserEventHistoryServices;
use App\Services\TwigServices;
use App\Services\Marketing\PromocodeServices;
use App\Services\QueueServices;
// Symfony
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Interfaces\PaymentProvider;

class PaymentServices
{
    // Заказ создан
    public const ORDER_CREATED = 1;
    // Заказ оплачен
    public const ORDER_PAID = 2;
    // Заказ отменен
    public const ORDER_CANCELED = 3;

    private const ACCESS_DEFAULT = 'standard';
    private const ACCESS_VIP = 'vip';

    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_CONSULTATION = 'consultation';

    private const MONTH_DAYS = 31;

    // Repository
    private RecostingRepository $recostingRepository;
    private InvoiceRepository $invoiceRepository;
    private TariffsRepository $tariffsRepository;
    private SiteSettingsRepository $siteSettingsRepository;
    private SpecialistsRequestsRepository $specialistsRequestsRepository;
    // Services
    private QueueServices $queueServices;
    private PromocodeServices $promocodeServices;
    private SubscriptionHistoryServices $subscriptionHistoryServices;
    private UserEventHistoryServices $userEventHistoryServices;

    // Etc
    private EntityManagerInterface $em;
    private PaymentProvider $paymentProvider;
    private KernelInterface $kernel;
    private ParameterBagInterface $params;

    public function __construct(
        EntityManagerInterface $em,
        InvoiceRepository $invoiceRepository,
        TariffsRepository $tariffsRepository,
        PaymentProvider $paymentProvider,
        KernelInterface $kernel,
        SubscriptionHistoryServices $subscriptionHistoryServices,
        UserEventHistoryServices $userEventHistoryServices,
        RecostingRepository $recostingRepository,
        PromocodeServices $promocodeServices,
        SpecialistsRequestsRepository $specialistsRequestsRepository,
        SiteSettingsRepository $siteSettingsRepository,
        QueueServices $queueServices,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->invoiceRepository = $invoiceRepository;
        $this->tariffsRepository = $tariffsRepository;
        $this->paymentProvider = $paymentProvider;
        $this->kernel = $kernel;
        $this->subscriptionHistoryServices = $subscriptionHistoryServices;
        $this->userEventHistoryServices = $userEventHistoryServices;
        $this->recostingRepository = $recostingRepository;
        $this->promocodeServices = $promocodeServices;
        $this->specialistsRequestsRepository = $specialistsRequestsRepository;
        $this->siteSettingsRepository = $siteSettingsRepository;
        $this->queueServices = $queueServices;
        $this->params = $params;
    }

    /**
     * Создание Invoice
     *
     * @param User $user
     * @param mixed $data
     *
     * @return [type]
     */
    public function createInvoice(User $user, int $tariff_id, string $promocode)
    {
        $tariff = $this->tariffsRepository->findOneBy(['id' => $tariff_id]);
        $invoice = new Invoice();

        if (!empty($promocode)) {
            try {
                $this->promocodeServices->validatePaymentPromocode($promocode, $user);
            } catch (LogicException $e) {
                $promocode = null;
                throw new LogicException($e->getMessage());
            }
        }

        if (!$tariff) {
            throw new LogicException('Тариф не найден');
        }

        $price = $tariff->getPrice();
        if ($promocode) {
            $price = $this->getPromoDiscountPrice($user, $tariff, $promocode);
            if ($price != $tariff->getPrice()) {
                $invoice->setPromocode($this->promocodeServices->getPromoByCode($promocode));
            }
        }

        // Создаем Invoice
        $invoice->setUser($user)
            ->setPrice($price)
            ->setStatus(self::ORDER_CREATED)
            ->setCreateTime(time())
            ->setPaymentSystem($this->paymentProvider->getPaymentSystemName())
            ->setTariff($tariff)
            ->setType(self::TYPE_SUBSCRIPTION);

        // Создание рекурентного платежа
        $is_recurrent = $this->params->get('robokassa.is_recurrent');
        if ($is_recurrent) {
            $invoice->setIsRecurring(true);
        }

        $this->em->persist($invoice);
        $this->em->flush();

        // Получаем ссылку для оплаты
        if ($invoice->getId()) {
            return $this->paymentProvider->getPaymentLink(
                $invoice->getId(),
                $price,
                $tariff->getNumberMonths()
            );
        }
    }

    /**
     * Создание инвойса для рекурентного платежа
     *
     * @param User $user
     * @param SubscriptionHistory $last_subscription
     *
     * @return Invoice
     */
    public function createRecurrentInvoice(User $user, SubscriptionHistory $last_subscription): Invoice
    {
        $invoice = new Invoice();
        // Создаем Invoice
        $invoice->setUser($user)
            ->setPrice($last_subscription->getInvoice()->getTariff()->getPrice())
            ->setStatus(self::ORDER_CREATED)
            ->setCreateTime(time())
            ->setPaymentSystem($this->paymentProvider->getPaymentSystemName())
            ->setTariff($last_subscription->getInvoice()->getTariff())
            ->setType(self::TYPE_SUBSCRIPTION)
            ->setIsRecurring(true)
            ->setIsAuto(true)
            ->setRecurrentParent($last_subscription->getInvoice());
        $this->em->persist($invoice);
        $this->em->flush();

        // Задаем для родителя дату последнего созданного инвойса
        $parent_invoice = $last_subscription->getInvoice();
        $parent_invoice->setRecurringLastTime(time());
        $this->em->persist($parent_invoice);
        $this->em->flush();

        return $invoice;
    }

    /**
     * Повторение оплаты пользователя
     *
     * @param User $user
     * @param Invoice $recurrent_invoice
     * @param mixed $parent_invoice
     *
     * @return [type]
     */
    public function createRecurrentPayment(User $user, Invoice $recurrent_invoice, $parent_invoice)
    {
        return $this->paymentProvider->recurring(
            $parent_invoice->getId(),
            $recurrent_invoice->getId(),
            $parent_invoice->getTariff()->getPrice(),
            $parent_invoice->getTariff()->getNumberMonths()
        );
    }

    /**
     * Проверка рекурентного платежа
     *
     * @param Invoice $parent_invoice
     *
     * @return bool
     */
    public function validateRecurrentPayment(Invoice $parent_invoice): bool
    {
        // Доп защита. Проверяем, что бы прошлая попытка списания была не раньше чем час назад
        if ($parent_invoice->getRecurringLastTime()) {
            // Сколько часов назад была прошлая попытка
            $datediff =  time() - $parent_invoice->getRecurringLastTime();
            $hours = floatval($datediff / (60 * 60));
            // Если прошлая попытка была меньше часа назад, то ничего не делаем, ждем ответ от системы
            if ($hours < 1) {
                return false;
            }
        }

        // Обновляем дату для родителя
        $parent_invoice->setRecurringAttempts($parent_invoice->getRecurringAttempts() + 1);
        $this->em->persist($parent_invoice);
        $this->em->flush();

        // Смотрим прошлый платеж
        $recurrent = $this->invoiceRepository->findOneBy([
            'recurrent_parent' => $parent_invoice->getId(),
            'is_auto' => 1
        ], ['id' => 'DESC']);

        // Если это первый авто платеж, то выполняем
        if (!$recurrent) {
            return true;
        }

        // Проверяем что бы прошлый платеж был сегодня
        if (date('Y-m-d', time()) == date('Y-m-d', $recurrent->getCreateTime())) {
            // Если прошлый платеж прошел, то не делаем новых
            if ($recurrent->getStatus() == self::ORDER_PAID) {
                // Сбрасываем счетчик
                $parent_invoice->setRecurringAttempts(0);
                $this->em->persist($parent_invoice);
                $this->em->flush();

                return false;
            }
        }

        // Если попытка списать деньги больше 3, то не выполняем списание
        if ($parent_invoice->getRecurringAttempts() > 3) {
            // Сбрасываем счетчик, выключаем автоплатеж
            $parent_invoice->setRecurringAttempts(0)->setIsCanceled(true);
            $this->em->persist($parent_invoice);
            $this->em->flush();

            return false;
        }

        return true;
    }

    /**
     * Создание ссылки на оплату консультации
     *
     * @param mixed $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function createSpecialistInvoice($user, $form)
    {
        $specialist = $form->get('specialist')->getData();
        $fio = $form->get('fio')->getData();
        $phone = $form->get('phone')->getData();
        $email = $form->get('email')->getData();
        $comment = $form->get('comment')->getData();

        // Создаем Invoice
        $invoice = new Invoice();
        $invoice->setUser($user)
            ->setPrice($specialist->getPrice())
            ->setStatus(self::ORDER_CREATED)
            ->setCreateTime(time())
            ->setPaymentSystem($this->paymentProvider->getPaymentSystemName())
            ->setTariff(null)
            ->setType(self::TYPE_CONSULTATION);
        $this->em->persist($invoice);
        $this->em->flush();

        $request = new SpecialistsRequests();
        $request
            ->setSpecialist($specialist)
            ->setFio($fio)
            ->setPhone($phone)
            ->setEmail($email)
            ->setComment($comment)
            ->setCreateTime(time())
            ->setInvoice($invoice);
        $this->em->persist($request);
        $this->em->flush();

        // Получаем ссылку для оплаты
        if ($invoice->getId()) {
            return $this->paymentProvider->getPaymentLink(
                $invoice->getId(),
                $specialist->getPrice(),
                0
            );
        }

        throw new LogicException('Произошла ошибка');
    }

    /**
     * Получение информации о invoice
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getInvoices(User $user)
    {
        $invoices = $this->invoiceRepository->findBy(['user' => $user->getId()]);
        $result = [];
        if ($invoices) {
            foreach ($invoices as $invoice) {
                $result[] = [
                    'id' => $invoice->getId(),
                    'price' => $invoice->getPrice(),
                    'status' => $invoice->getStatus(),
                    'payment_system' => $invoice->getPaymentSystem(),
                    'create_time' => date("Y-m-d H:i:s", $invoice->getCreateTime()),
                ];
            }
        }

        return $result;
    }

    /**
     * @param mixed $params
     *
     * @return [type]
     */
    public function successPayment($params)
    {
        // Чтение параметров
        $inv_id = $this->paymentProvider->getInvoiceId($params);

        if (empty($inv_id)) {
            throw new LogicException('Invoice Id не найден');
        } else {
            $invoice = $this->invoiceRepository->findOneBy(['id' => $inv_id]);
            if (!empty($invoice)) {
                if ($this->paymentProvider->isValidSuccess($params)) {
                    // Если dev, то ставим оплату успешной
                    if ($this->kernel->getEnvironment() == 'dev') {
                        $invoice->setStatus(self::ORDER_PAID);
                        $this->em->persist($invoice);
                        $this->em->flush();

                        // Запись подписки
                        if ($invoice->getType() == self::TYPE_SUBSCRIPTION) {
                            $this->updateSubscription($invoice);
                            if ($invoice->getPromocode()) {
                                $this->promocodeServices->savePromocode($invoice->getUser(), $invoice->getPromocode());
                            }
                        }

                        // Запись консультации
                        if ($invoice->getType() == self::TYPE_CONSULTATION) {
                            $this->updateConsultation($invoice);
                        }

                        $this->userEventHistoryServices->saveUserEvent(
                            $invoice->getUser(),
                            'payment',
                            $invoice->getPrice()
                        );
                    }
                    return true;
                } else {
                    throw new LogicException('Invoice Id не найден');
                }
            } else {
                throw new LogicException('Произошла ошибка');
            }
        }
    }

    /**
     * @param mixed $params
     *
     * @return [type]
     */
    public function resultPayment($params)
    {
        $inv_id = $this->paymentProvider->getInvoiceId($params);

        if (!empty($inv_id)) {
            $invoice = $this->invoiceRepository->findOneBy(['id' => $inv_id]);
            if (!empty($invoice)) {
                if ($this->paymentProvider->isValidResult($params)) {
                    // Обновление статуса
                    $invoice->setStatus(self::ORDER_PAID);
                    $this->em->persist($invoice);
                    $this->em->flush();

                    // Запись подписки
                    if ($invoice->getType() == self::TYPE_SUBSCRIPTION) {
                        $this->updateSubscription($invoice);
                        if ($invoice->getPromocode()) {
                            $this->promocodeServices->savePromocode($invoice->getUser(), $invoice->getPromocode());
                        }
                    }

                    // Запись консультации
                    if ($invoice->getType() == self::TYPE_CONSULTATION) {
                        $this->updateConsultation($invoice);
                    }

                    $this->userEventHistoryServices->saveUserEvent(
                        $invoice->getUser(),
                        'payment',
                        $invoice->getPrice()
                    );
                    echo "OK$inv_id\n";
                    exit();
                }
            }
        }

        throw new LogicException('Произошла ошибка');
    }

    /**
     * @param mixed $params
     *
     * @return [type]
     */
    public function errorPayment($params)
    {
        $inv_id = $this->paymentProvider->getInvoiceId($params);

        if (!empty($inv_id)) {
            $invoice = $this->invoiceRepository->findOneBy(['id' => $inv_id]);
            if ($invoice) {
                // Обновление статуса
                $invoice->setStatus(self::ORDER_CANCELED);
                $this->em->persist($invoice);
                $this->em->flush();
                return true;
            }
        }

        throw new LogicException('Произошла ошибка');
    }

    /**
     * Обновление или создание подписки
     *
     * @param Invoice $invoice
     *
     * @return SubscriptionHistory
     */
    public function updateSubscription(Invoice $invoice): SubscriptionHistory
    {
        $user = $invoice->getUser();
        $is_vip = false;
        $subscription_history = new SubscriptionHistory();
        if (!$user) {
            throw new LogicException('Произошла ошибка. Пользователь не найден!');
        }

        if ($invoice->getTariff()->getType() == self::ACCESS_VIP) {
            $is_vip = true;
        }

        // Кол-во месяцев в тарифе
        $month = $invoice->getTariff()->getNumberMonths();
        // Тип подписки
        $sub_type = $invoice->getTariff()->getType();

        // Получение последней оплаты
        $last_recosting = $this->recostingRepository->findOneBy(
            ['user' => $user],
            ['id' => 'DESC'],
            1,
            0
        );

        // Если активная подписка у пользователя
        if (!empty($user->getSubscriptionEndDate()) && $user->getSubscriptionEndDate() > time()) {
            // Проверяем нужен ли перерасчет. Если подписка активна и меняется тип тарифа
            if ($last_recosting && $last_recosting->getIsVip() != $is_vip) {
                // Перерасчет, если другой тариф
                $days = (float)$this->recalculation(
                    $last_recosting,
                    $invoice
                );

                $subscription_history->setDescription(trim("Перерасчет подписки на " .
                    TwigServices::plural($month, ['месяц', 'месяца', 'месяцев']) .
                    ". Тип подписки $sub_type. Подписка до " .
                    date("d.m.Y H:i", time() + ($days * 86400))));
                $user->setSubscriptionEndDate(time() + ($days * 86400));
            } elseif ($last_recosting && $last_recosting->getIsVip() == $is_vip) {
                // Обновление подписки
                $this->renewSubscription($last_recosting, $invoice);

                $user->setSubscriptionEndDate(strtotime("+$month month", $user->getSubscriptionEndDate()));
                $subscription_history->setDescription(trim("Продление подписки на " .
                    TwigServices::plural($month, ['месяц', 'месяца', 'месяцев']) .
                    ". Тип подписки - " . $sub_type . ".  Подписка до " .
                    date("d.m.Y H:i", $user->getSubscriptionEndDate())));
            } else {
                $user->setSubscriptionEndDate(strtotime("+$month month", $user->getSubscriptionEndDate()));
                $subscription_history->setDescription(trim("Оплата подписки на " .
                    TwigServices::plural($month, ['месяц', 'месяца', 'месяцев']) .
                    ". Тип подписки - " . $sub_type . ".  Подписка до " .
                    date("d.m.Y H:i", $user->getSubscriptionEndDate())));
            }

            $this->em->persist($user);
            $this->em->flush();
        } else {
            //Добавляем кол-во месяцев
            $user->setSubscriptionEndDate(strtotime("+$month month", time()));
            $this->em->persist($user);
            $this->em->flush();

            $recosting = new Recosting();
            $recosting
                ->setUser($user)
                ->setIsVip($is_vip)
                ->setSubscriptionFrom(time())
                ->setSubscriptionTo($user->getSubscriptionEndDate())
                ->setTariffPrice($invoice->getTariff()->getPrice())
                ->setTotalPrice($invoice->getTariff()->getPrice());
            $this->em->persist($recosting);
            $this->em->flush();

            $subscription_history->setDescription(($invoice->getIsRecurring() ? "Продление" : "Оплата") . " " .
                trim("подписки на " . TwigServices::plural($month, ['месяц', 'месяца', 'месяцев']) .
                    ". Тип подписки - " . $invoice->getTariff()->getType() . ".  Подписка до " .
                    date("d.m.Y H:i", $user->getSubscriptionEndDate())));
        }

        // Сохраняем историю
        $subscription_history
            ->setUser($user)
            ->setType($this->subscriptionHistoryServices::TYPE_PAY)
            ->setPrice($invoice->getPrice())
            ->setCreateTime(time())
            ->setSubscriptionFrom(time())
            ->setSubscriptionTo($user->getSubscriptionEndDate())
            ->setInvoice($invoice)
            ->setIsVip($is_vip);

        $this->em->persist($subscription_history);
        $this->em->flush();

        return $subscription_history;
    }

    /**
     * Запись в sub history информации о записи на консультацию
     *
     * @param Invoice $invoice
     *
     * @return [type]
     */
    public function updateConsultation(Invoice $invoice): SubscriptionHistory
    {
        $user = $invoice->getUser();
        $subscription_history = new SubscriptionHistory();
        if (!$user) {
            throw new LogicException('Произошла ошибка. Пользователь не найден!');
        }

        $specialist_request = $this->specialistsRequestsRepository->findOneBy(['invoice' => $invoice->getId()]);
        if (!$specialist_request) {
            throw new LogicException('Произошла ошибка. Заявка на консультацию не найдена!');
        }

        $specialist = $specialist_request->getSpecialist();

        // Сохраняем историю
        $subscription_history
            ->setUser($user)
            ->setType($this->subscriptionHistoryServices::TYPE_PAY)
            ->setPrice($invoice->getPrice())
            ->setCreateTime(time())
            ->setSubscriptionFrom(time())
            ->setSubscriptionTo(time())
            ->setInvoice($invoice);

        $subscription_history->setDescription(trim("Оплата консультации. Специалист " .
            $specialist->getFio() . '.'));

        $this->em->persist($subscription_history);
        $this->em->flush();

        $requests_email = $this->siteSettingsRepository->findOneBy(['code' => 'specialists_requests_email']);
        if ($requests_email) {
            // Отправка email админу
            $this->queueServices->sendEmail(
                $requests_email->getValue(),
                'Поступила заявка на консультацию',
                '/mail/specialists_requests/admin_new_request.html.twig',
                [
                    'user_fio' => $specialist_request->getFio(),
                    'user_phone' => $specialist_request->getPhone(),
                    'user_email' => $specialist_request->getEmail(),
                    'user_comment' => $specialist_request->getComment(),
                    'specialist_fio' => $specialist_request->getSpecialist()->getFio()
                ]
            );
        }

        // Отправка email специалисту
        $this->queueServices->sendEmail(
            $specialist_request->getSpecialist()->getEmail(),
            'Поступила заявка на консультацию',
            '/mail/specialists_requests/specialist_new_request.html.twig',
            []
        );

        // Отправка email пользователю
        $this->queueServices->sendEmail(
            $user->getEmail(),
            'Оплата консультации специалиста прошла успешно!',
            '/mail/specialists_requests/user_new_request.html.twig',
            [
                'specialist_fio' => $specialist_request->getSpecialist()->getFio()
            ]
        );

        return $subscription_history;
    }

    /**
     * Перерасчет стоимости
     *
     * @param Recosting $last_recosting
     * @param Invoice $invoice
     *
     * @return [type]
     */
    public function recalculation(Recosting $last_recosting, Invoice $invoice): float
    {
        $added_days = 0;

        // Сколько дней подписки осталось
        $datediff = $last_recosting->getSubscriptionTo() - time();
        $days = floatval($datediff / (60 * 60 * 24));

        //Один день старого тарифа
        $last_day_price = $last_recosting->getTariffPrice() / self::MONTH_DAYS;
        // Сколько денег осталось
        $remaining_balance = round($days * $last_day_price, 2);

        // Общий баланс
        $balance = round($remaining_balance + $invoice->getTariff()->getPrice(), 2);

        // Сколько стоит день нового тарифа
        $one_day_price = $invoice->getTariff()->getPrice() / self::MONTH_DAYS;

        //Кол-во дней с новым тарифом
        $added_days = $balance / $one_day_price;

        $this->updateRecosting($last_recosting, $invoice, $balance, $remaining_balance, $added_days);

        return $added_days;
    }

    /**
     * Обновляем recosting для продления оплаты
     * Сбрасываем старый recosting и создаем новый с полным диапазоном
     *
     * @param Recosting $last_recosting
     * @param Invoice $invoice
     *
     * @return [type]
     */
    public function renewSubscription(Recosting $last_recosting, Invoice $invoice)
    {
        $is_vip = $invoice->getTariff()->getType() == self::ACCESS_VIP;
        $recosting = new Recosting();
        $month = $invoice->getTariff()->getNumberMonths();
        $recosting
            ->setUser($last_recosting->getUser())
            ->setIsVip($is_vip)
            ->setTariffPrice($invoice->getTariff()->getPrice())
            ->setTotalPrice($last_recosting->getTotalPrice() + $invoice->getTariff()->getPrice())
            ->setSubscriptionFrom(time())
            ->setSubscriptionTo(strtotime("+$month month", $last_recosting->getSubscriptionTo()));

        $last_recosting->setSubscriptionTo(time());
        $this->em->persist($last_recosting);
        $this->em->flush();
        $this->em->persist($recosting);
        $this->em->flush();

        return $last_recosting;
    }

    /**
     * Отмена рекуррентного платежа
     *
     * @param User $user
     * @param int $invoice_id
     *
     * @return [type]
     */
    public function cancelAutoPayment(User $user, int $invoice_id)
    {
        $invoice = $this->invoiceRepository->findOneBy(['user' => $user, 'id' => $invoice_id]);
        if (!$invoice) {
            throw new LogicException("Платеж не найден");
        }

        if ($invoice->getIsCanceled()) {
            throw new LogicException("Автосписание уже выключено");
        }

        $invoice->setIsCanceled(true);
        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    /**
     * Информация о перерасчете
     *
     * @param User $user
     * @param int $tariff_id
     *
     * @return [type]
     */
    public function userRecostingInfo(User $user, int $tariff_id, string $promocode)
    {
        $tariff = $this->tariffsRepository->findOneBy(['id' => $tariff_id]);
        if (!$tariff) {
            throw new LogicException("Тариф не найден");
        }

        $is_promo = false;
        $old_tariff_type = null;
        $current_tariff_price = null;
        $subscription_to = time() + (self::MONTH_DAYS * 86400);
        $days = self::MONTH_DAYS;
        $added_days = $tariff->getNumberMonths() * self::MONTH_DAYS;
        $current_tariff_month = null;
        $new_tariff_month = $tariff->getNumberMonths();

        // Получение последней оплаты
        $last_recosting = $this->recostingRepository->findOneBy(
            ['user' => $user],
            ['id' => 'DESC'],
            1,
            0
        );

        // Если подписка не закончилась
        if (!empty($user->getSubscriptionEndDate()) && $user->getSubscriptionEndDate() > time()) {
            // Проверяем нужен ли перерасчет
            if ($last_recosting) {
                // Сколько дней подписки осталось
                $datediff = $last_recosting->getSubscriptionTo() - time();
                $days = floatval($datediff / (60 * 60 * 24));

                //Один день старого тарифа
                $last_day_price = $last_recosting->getTariffPrice() / self::MONTH_DAYS;
                // Сколько денег осталось
                $remaining_balance = $days * $last_day_price;

                // Общий баланс
                $balance = $remaining_balance + $tariff->getPrice();

                // Сколько стоит день нового тарифа
                $one_day_price = round($tariff->getPrice() / self::MONTH_DAYS, 2);

                //Кол-во дней с новым тарифом
                $added_days = $balance / $one_day_price;

                $current_tariff_price = $tariff->getPrice();
                $current_tariff_month = $tariff->getNumberMonths();
                if ($promocode) {
                    $current_tariff_price = $this->getPromoDiscountPrice($user, $tariff, $promocode);
                    $is_promo = true;
                }
                //$new_tariff_price = $tariff->getPrice();

                $old_tariff_type = $last_recosting->getIsVip() ? self::ACCESS_VIP : self::ACCESS_DEFAULT;

                if (!$tariff->getIsActive()) {
                    $tariff_number_month = $tariff->getNumberMonths();
                    $tariffs = $this->tariffsRepository->findBy([
                        'is_active' => true,
                        'type' => $tariff->getType()
                    ], ['number_months' => 'ASC']);

                    if (!$tariffs) {
                        throw new LogicException("Ошибка поиска активного тарифа");
                    }

                    $tariff = null;
                    foreach ($tariffs as $active_tariff) {
                        if ($active_tariff->getNumberMonths() == $tariff_number_month) {
                            $tariff = $active_tariff;
                            break;
                        }
                    }
                    if (empty($tariff)) {
                        $tariff = $tariffs[0];
                    }

                    $new_tariff_price = $tariff->getPrice();
                    $new_tariff_month = $tariff->getNumberMonths();
                    if ($promocode) {
                        $new_tariff_price = $this->getPromoDiscountPrice($user, $tariff, $promocode);
                        if ($new_tariff_price != $tariff->getPrice()) {
                            $is_promo = true;
                        }
                    }
                } else {
                    $new_tariff_price = null;
                }

                $subscription_to = $added_days % self::MONTH_DAYS;
                $days = $added_days % self::MONTH_DAYS;
            }
        } else {
            $new_tariff_price = $tariff->getPrice();

            // Проверка промокода
            if ($promocode) {
                $new_tariff_price = $this->getPromoDiscountPrice($user, $tariff, $promocode);
                if ($new_tariff_price != $tariff->getPrice()) {
                    $is_promo = true;
                }
            }
        }

        return [
            'current_tariff_price' => $current_tariff_price,
            'new_tariff_price' => $new_tariff_price,
            'added_days' => $added_days,
            'current_tariff_month' => $current_tariff_month,
            'new_tariff_month' => $new_tariff_month,
            'days' => self::MONTH_DAYS,
            'subscription_to' => $subscription_to,
            'old_tariff_type' => $old_tariff_type,
            'new_tariff_type' => $tariff->getType(),
            'is_promocode' => $is_promo
        ];
    }

    /**
     * Обновление данных пересчета в базе
     *
     * @param Recosting $last_recosting
     * @param float $balance
     * @param Invoice $invoice
     * @param float $remaining_balance
     * @param float $added_days
     *
     * @return [type]
     */
    private function updateRecosting(
        Recosting $last_recosting,
        Invoice $invoice,
        float $balance,
        float $remaining_balance,
        float $added_days
    ): Recosting {
        $is_vip = $invoice->getTariff()->getType() == self::ACCESS_VIP;
        $recosting = new Recosting();

        $recosting
            ->setUser($last_recosting->getUser())
            ->setIsVip($is_vip)
            ->setTariffPrice($invoice->getTariff()->getPrice())
            ->setTotalPrice($balance)
            ->setRemainingPrice($remaining_balance)
            ->setSubscriptionFrom(time())
            ->setSubscriptionTo(time() + ($added_days * 86400));

        $last_recosting->setSubscriptionTo(time());

        $this->em->persist($last_recosting);
        $this->em->flush();
        $this->em->persist($recosting);
        $this->em->flush();

        return $last_recosting;
    }

    /**
     * Получить скидку по тарифу с промокодом
     *
     * @param mixed $user
     * @param mixed $tariff
     * @param mixed $promocode
     *
     * @return [type]
     */
    private function getPromoDiscountPrice($user, $tariff, $promocode)
    {
        $new_tariff_price = $tariff->getPrice();
        // Рабочий ли промокод
        try {
            $promocode = $this->promocodeServices->validatePaymentPromocode($promocode, $user);
        } catch (LogicException $e) {
            return $new_tariff_price;
        }

        // Выставляем новую цену если рабочий
        if ($promocode) {
            $new_tariff_price = ($new_tariff_price / 100) * (100 - $promocode->getDiscountPercent());
        }

        return $new_tariff_price;
    }
}
