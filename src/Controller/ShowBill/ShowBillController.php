<?php

namespace App\Controller\ShowBill;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Payment\PaymentServices;
use App\Services\Materials\MaterialsServices;

class ShowBillController extends BaseApiController
{
    /**
     * Афиша
     *
     * @Route("/panel/showbill", name="panel_show_bill", methods={"GET"})
     */
    public function successPay(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices
    ) {
        $user = $security->getUser();
        $type = $request->query->get('type');
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_DESC) ? self::SORT_DESC : self::SORT_ASC;

        $materials = $materialsServices->getShowBillMaterials($user, $order_by);

        return $this->render('/pages/showbill/list.html.twig', [
            'title' => 'Афиша',
            'materials' => $materials,
            'type' => $type,
        ]);
    }
}
