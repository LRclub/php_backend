<?php

namespace App\Services\Settings;

use App\Entity\User;
use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;

class SettingsServices
{
    private SiteSettingsRepository $siteSettingsRepository;

    public function __construct(
        SiteSettingsRepository $siteSettingsRepository,
        EntityManagerInterface $em
    ) {
        $this->siteSettingsRepository = $siteSettingsRepository;
        $this->em = $em;
    }

    /**
     * Редактирование настройки
     *
     * @param mixed $id
     *
     * @return [type]
     * @param mixed $form
     */
    public function updateSetting($form)
    {
        $id = $form->get('setting_id')->getData();
        $name = $form->get('name')->getData();
        $code = $form->get('code')->getData();
        $value = $form->get('value')->getData();

        if ($id === 0) {
            $siteSettings = new SiteSettings();
        } else {
            $siteSettings = $this->siteSettingsRepository->find($id);
            if (empty($siteSettings)) {
                throw new LogicException('ID не найден');
            }
        }

        $siteSettings->setName($name)->setCode($code)->setValue($value);
        $this->em->persist($siteSettings);
        $this->em->flush();
    }

    /**
     * Удаление настройки
     *
     * @param mixed $id
     *
     * @return [type]
     * @param mixed $form
     */
    public function deleteSetting($id)
    {
        $siteSettings = $this->siteSettingsRepository->find($id);
        if (empty($siteSettings)) {
            throw new LogicException('Ошибка');
        }

        $this->em->remove($siteSettings);
        $this->em->flush();
    }

    /**
     * Получение по Id параметров
     *
     * @param mixed $id
     *
     * @return [type]
     */
    public function getSettingById($id)
    {
        $setting = $this->siteSettingsRepository->find($id);
        if (!$setting) {
            return false;
        }

        $result = [
            'id' => $setting->getId(),
            'name' => $setting->getName(),
            'code' => $setting->getCode(),
            'value' => $setting->getValue(),
        ];

        return $result;
    }
}
