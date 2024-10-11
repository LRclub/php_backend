<?php

namespace App\Interfaces;

interface PaymentProvider
{
    /**
     * Получение ссылки на оплату
     *
     * @param int $order_id
     * @param float $price
     *
     * @return string
     */
    public function getPaymentLink(int $order_id, float $price, int $count_months): string;

    /**
     * Создание рекурентного платежа
     *
     * @param int $order_id
     * @param float $price
     *
     * @return string
     */
    public function recurring(int $parent_order_id, int $order_id, float $price, int $count_months);

    /**
     * Успешная оплата
     *
     * @param array $data
     *
     * @return bool
     */
    public function isValidSuccess(array $data): bool;

    /**
     * Result URL
     *
     * @param array $data
     *
     * @return bool
     */
    public function isValidResult(array $data): bool;

    /**
     * @param array $params
     *
     * @return int
     */
    public function getInvoiceId(array $params): int;

    /**
     * @return string
     */
    public function getPaymentSystemName(): string;
}
