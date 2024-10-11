<?php

namespace App\Services;

use App\Services\Admin\AdminSpecialistsServices;
use App\Services\Materials\MaterialsServices;
use Symfony\Component\Security\Core\User\UserInterface;

class PanelServices
{
    private AdminSpecialistsServices $adminSpecialistsServices;
    private MaterialsServices $materialsServices;

    public function __construct(
        AdminSpecialistsServices $adminSpecialistsServices,
        MaterialsServices $materialsServices
    ) {
        $this->adminSpecialistsServices = $adminSpecialistsServices;
        $this->materialsServices = $materialsServices;
    }

    /**
     * Возвращаем информацию для главной
     *
     * @param UserInterface $user
     * @return array
     */
    public function getData(UserInterface $user)
    {
        $specialists = $this->adminSpecialistsServices->getSpecialists(null, [
            'sort_param' => 'sort',
            'sort_type' => 'asc'
        ], 5);

        $streams = $this->materialsServices->getMainPageStreams($user);
        $billboards = $this->materialsServices->getShowBillMaterials($user, [
            'limit' => MaterialsServices::MAIN_PAGE_ITEMS,
            'sort_param' => 'date',
            'sort_type' => 'desc'
        ]);
        $materials = $this->materialsServices->getMainPageMaterials($user);

        return [
            'streams' => $streams,
            'billboards' => $billboards,
            'materials' => $materials,
            'specialists' => $specialists
        ];
    }
}
