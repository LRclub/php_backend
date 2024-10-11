<?php

namespace App\Services\Marketing;

use App\Entity\User;
use App\Entity\UTM;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class UTMServices
{
    private const REGISTER_ACTION = 'register';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Создание запроса на смену пароля
     *
     * @param User $user
     */
    public function registrationTags(User $user, FormInterface $form)
    {
        $utm = $form->get('utm')->getData();
        if (!empty($utm)) {
            foreach ($utm as $val) {
                $item = new UTM();
                $item
                    ->setUser($user)
                    ->setAction(self::REGISTER_ACTION)
                    ->setVisitTime($val['time'])
                    ->setUtmCampaign($val['utm_campaign'] ?? "")
                    ->setUtmContent($val['utm_content'] ?? "")
                    ->setUtmMedium($val['utm_medium'] ?? "")
                    ->setUtmSource($val['utm_source'] ?? "")
                    ->setUtmTerm($val['utm_term'] ?? "");

                $this->em->persist($item);
            }
        }

        $this->em->flush();
    }

    /**
     * Убираем битые UTM метки
     *
     * @param array $data
     * @return array
     */
    public function filterBrokenTags(array $data)
    {
        //на тот случай, если утм меток очень много, срезаем до определенного значения
        if (count($data) > 50) {
            $data = array_slice($data, -50);
        }

        return array_filter($data, function ($v) {
            $utm_source = trim($v['utm_source'] ?? '');
            $utm_medium = trim($v['utm_medium'] ?? '');
            $utm_campaign = trim($v['utm_campaign'] ?? '');
            $utm_term = trim($v['utm_term'] ?? '');
            $utm_content = trim($v['utm_content'] ?? '');
            $time = $v['time'] ?? false;

            $is_valid = !(empty($time) ||
                !is_numeric($time) ||
                strlen($time) > 11 ||
                (empty($utm_source) &&
                    empty($utm_medium) &&
                    empty($utm_campaign) &&
                    empty($utm_term) &&
                    empty($utm_content))
            );

            return $is_valid;
        });
    }
}
