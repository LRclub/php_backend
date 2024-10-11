<?php

namespace App\Controller\Api\System;

use App\Controller\Base\BaseApiController;
use App\Repository\CountriesRepository;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;

class SystemController extends BaseApiController
{
    /**
     * Получение списка стран
     *
     * @Route("/api/countries", name="api_countries", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="countries")
     */
    public function getCountriesAction(
        CountriesRepository $countriesRepository
    ) {
        return $this->jsonSuccess(['result' => $countriesRepository->getCountries()]);
    }
}
