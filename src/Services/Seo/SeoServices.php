<?php

namespace App\Services\Seo;

use App\Entity\User;
use App\Entity\Seo;
use App\Repository\SeoRepository;
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;

class SeoServices
{
    private SeoRepository $seoRepository;

    public function __construct(
        SeoRepository $seoRepository,
        EntityManagerInterface $em
    ) {
        $this->seoRepository = $seoRepository;
        $this->em = $em;
    }

    /**
     * Получение seo данных через линк
     *
     * @param mixed $path
     *
     * @return [type]
     */
    public function getSeoByPath($path)
    {
        $seo = $this->seoRepository->findOneBy(['link' => $path]);
        if (!$seo) {
            return [];
        }

        return [
            'id' => $seo->getId(),
            'link' => $seo->getLink(),
            'title' => $seo->getTitle(),
            'description' => $seo->getDescription(),
            'keywords' => $seo->getKeywords(),
            'title_page' => $seo->getTitlePage(),
            'property' => $seo->getProperty()
        ];
    }

    /**
     * Редактирование настройки
     *
     * @param mixed $id
     *
     * @return [type]
     * @param mixed $form
     */
    public function updateSeo($form)
    {
        $id = $form->get('seo_id')->getData();
        $link = $form->get('link')->getData();
        $title = $form->get('title')->getData();
        $description = $form->get('description')->getData();
        $keywords = $form->get('keywords')->getData();
        $property = $form->get('property')->getData();
        $title_page = $form->get('title_page')->getData();

        if ($id === 0) {
            $seo = new Seo();
        } else {
            $seo = $this->seoRepository->find($id);
            if (empty($seo)) {
                throw new LogicException('Seo Параметр с таким ID не существует!');
            }
        }

        $path_exist = $this->seoRepository->findOneBy(['link' => $link]);
        if ($path_exist && $seo->getId() != $path_exist->getId()) {
            throw new LogicException('SEO параметры для этой страницы уже существуют');
        }

        $seo
            ->setLink($link)
            ->setTitle($title)
            ->setDescription($description)
            ->setKeywords($keywords)
            ->setProperty($property)
            ->setTitlePage($title_page);
        $this->em->persist($seo);
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
    public function deleteSeo($id)
    {
        $seo = $this->seoRepository->find($id);
        if (empty($seo)) {
            throw new LogicException('Ошибка');
        }

        $this->em->remove($seo);
        $this->em->flush();
    }

    /**
     * Получение по Id seo параметров
     *
     * @param mixed $id
     *
     * @return [type]
     */
    public function getSeoById($id)
    {
        $seo = $this->seoRepository->find($id);
        if (!$seo) {
            return false;
        }

        $result = [
            'id' => $seo->getId(),
            'link' => $seo->getLink(),
            'title' => $seo->getTitle(),
            'description' => $seo->getDescription(),
            'keywords' => $seo->getKeywords(),
            'title_page' => $seo->getTitlePage(),
            'property' => null
        ];

        foreach ($seo->getProperty() as $value) {
            $result['property'][] = [
                'name' => $value['name'],
                'content' => $value['content']
            ];
        }

        return $result;
    }
}
