<?php

namespace App\Services\Providers;

use App\Interfaces\PaymentProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Services\TwigServices;

class RobokassaServices implements PaymentProvider
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function getPaymentSystemName(): string
    {
        return 'Robokassa';
    }

    /**
     * Формирование ссылки для оплаты
     *
     * @param int $order_id
     * @param float $price
     * @param int $count_months
     *
     * @return string
     */
    public function getPaymentLink(int $order_id, float $price, int $count_months): string
    {
        $mrh_pass1 = $this->params->get('robokassa.pass1');
        $text_months = TwigServices::plural($count_months, ['месяц', 'месяца', 'месяцев']);
        $params = [
            'MerchantLogin' => $this->params->get('robokassa.login'),
            'InvId'         => $order_id,
            'Description'   => "Оплата подписки на " . $text_months,
            'OutSum'        => $price,
            'Culture'       => 'ru',
            'Encoding'      => 'utf-8',
            'IsTest'        => $this->params->get('robokassa.test') ?? null,
        ];

        // Создание рекурентного платежа
        $is_recurrent = $this->params->get('robokassa.is_recurrent');
        if ($is_recurrent) {
            $params['Recurring'] = true;
        }

        // Формирование подписи
        $signature = "{$params['MerchantLogin']}:{$params['OutSum']}:{$params['InvId']}:{$mrh_pass1}";
        $params['SignatureValue'] = md5($signature);
        $payment_link = 'https://auth.robokassa.ru/Merchant/Index.aspx?' . http_build_query($params);
        return iconv('windows-1251', 'utf-8', $payment_link);
    }

    /**
     * Создание рекурентного платежа
     *
     * @param int $parent_order_id
     * @param int $order_id
     * @param float $price
     * @param int $count_months
     *
     * @return [type]
     */
    public function recurring(int $parent_order_id, int $order_id, float $price, int $count_months)
    {
        $mrh_pass1 = $this->params->get('robokassa.pass1');
        $text_months = TwigServices::plural($count_months, ['месяц', 'месяца', 'месяцев']);
        $params = [
            'MerchantLogin'     => $this->params->get('robokassa.login'),
            'PreviousInvoiceId' => $parent_order_id,
            'InvoiceID'         => $order_id,
            'Description'       => "Продление подписки подписки на " . $text_months,
            'OutSum'            => $price,
            'Culture'           => 'ru',
            'Encoding'          => 'utf-8',
            'IsTest'            => $this->params->get('robokassa.test') ?? null,
        ];

        // Формирование подписи
        $signature = "{$params['MerchantLogin']}:{$params['OutSum']}:{$params['InvoiceID']}:{$mrh_pass1}";
        $params['SignatureValue'] = md5($signature);

        $ch = curl_init('https://auth.robokassa.ru/Merchant/Recurring');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Успешная оплата
     *
     * @param array $data
     *
     * @return bool
     */
    public function isValidSuccess(array $data): bool
    {
        // Пароль #1 (для тестовых платежей)
        $mrh_pass1 = $this->params->get('robokassa.pass1');
        $out_sum = $data['OutSum'];
        $inv_id = intval($data['InvId']);
        $crc = strtoupper($data['SignatureValue']);

        $my_crc = strtoupper(md5("$out_sum:$inv_id:$mrh_pass1"));
        if ($my_crc == $crc) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function isValidResult(array $data): bool
    {
        $out_sum = $data['OutSum'];
        $crc = strtoupper($data['SignatureValue']);
        $mrh_pass2 = $this->params->get('robokassa.pass2');
        $inv_id  = intval($data['InvId']);
        $out_sum = $data['OutSum'];
        $my_crc = strtoupper(md5("$out_sum:$inv_id:$mrh_pass2"));

        if ($my_crc == $crc) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function getInvoiceId(array $data): int
    {
        if (!isset($data['InvId'])) {
            return 0;
        }
        return $data['InvId'];
    }
}
