<?php

namespace App\Services\Marketing\PromocodeAction;

use App\Entity\Promocodes;
use App\Entity\User;
use App\Interfaces\PromocodesInterface;
use App\Repository\PromocodesRepository;
use App\Services\Marketing\PromocodeServices;
use App\Services\RandomizeServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\LogicException;

class PromocodeActionFactory
{
    private EntityManagerInterface $em;
    private PromocodesRepository $promocodesRepository;
    private DiscountAction $discountAction;
    private InviteAction $inviteAction;
    private PromocodeServices $promocodeServices;

    protected $actions = [];
    protected $promocode;
    protected $user;

    // Не допустить маунта PromocodeServices в классы подключаемые в констракте во избежание бесконечного цикла!
    public function __construct(
        EntityManagerInterface $em,
        PromocodesRepository $promocodesRepository,
        DiscountAction $discountAction,
        InviteAction $inviteAction
    ) {
        $this->em = $em;
        $this->promocodesRepository = $promocodesRepository;

        $this->addAction($discountAction);
        $this->addAction($inviteAction);
    }

    public function getPromoAction(
        Promocodes $promocode
    ) {
        if (!$promocode) {
            return null;
        }
        $this->setPromocode($promocode);

        $class_name = "App\Services\Marketing\PromocodeAction\\" . ucfirst($promocode->getAction()) . 'Action';

        return $this->getActionByClass($class_name);
    }

    private function getActionByClass($class_name): ?PromocodesInterface
    {
        if (empty($this->getPromocodeService())) {
            throw new LogicException('PromocodeService not exists!');
        }

        foreach ($this->actions as $action) {
            if (get_class($action) == $class_name) {
                $action
                    ->setPromocodeService($this->getPromocodeService())
                    ->setPromocode($this->getPromocode());
                return $action;
            }
        }

        return null;
    }

    public function getActionNames()
    {
        return array_keys($this->actions);
    }

    public function setPromocodeService(PromocodeServices $promocodeServices)
    {
        $this->promocodeServices = $promocodeServices;

        return $this;
    }

    public function setPromocode(Promocodes $promocode)
    {
        $this->promocode = $promocode;

        return $this;
    }

    protected function getPromocodeService()
    {
        return $this->promocodeServices;
    }

    protected function getPromocode()
    {
        return $this->promocode;
    }

    private function addAction(PromocodesInterface $action)
    {
        $name = $this->getActionTypeName($action);

        $this->actions[$name] = $action;
    }

    /**
     * Возвращаем имя на базе класса
     *
     * @param PromocodesInterface $action
     *
     * @return [type]
     */
    private function getActionTypeName(PromocodesInterface $action)
    {
        $class_name = get_class($action);
        $path = explode("\\", $class_name);
        $last = end($path);
        return substr($last, 0, -6);
    }
}
