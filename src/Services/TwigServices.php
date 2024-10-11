<?php

namespace App\Services;

use App\Entity\User;
use App\Services\User\UserServices;
use App\Services\Materials\MaterialsCategoriesServices;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\SiteSettingsRepository;
use Psr\Container\ContainerInterface;
use App\Services\Seo\SeoServices;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Services\File\FileServices;

class TwigServices
{
    private CoreSecurity $security;
    private UserServices $userServices;
    private ParameterBagInterface $params;
    private ContainerInterface $container;
    private SeoServices $seoServices;
    private $siteSettings;
    private RequestStack $requestStack;
    private FileServices $fileServices;
    private MaterialsCategoriesServices $materialsCategoriesServices;

    private $tokens = [
        [
            'token' => '/\[video\]([^\[\]]*)\[\/video\]/',
            'replacement' => '<video controls src="$1"></video>',
        ],
        [
            'token' => '/\[audio\]([^\[\]]*)\[\/audio\]/',
            'replacement' => '<audio controls src="$1"></audio>',
        ],
    ];

    public function __construct(
        CoreSecurity $security,
        UserServices $userServices,
        ParameterBagInterface $params,
        SiteSettingsRepository $siteSettingsRepository,
        ContainerInterface $container,
        SeoServices $seoServices,
        RequestStack $requestStack,
        FileServices $fileServices,
        MaterialsCategoriesServices $materialsCategoriesServices
    ) {
        $this->security = $security;
        $this->userServices = $userServices;
        $this->params = $params;
        $this->siteSettings = $siteSettingsRepository->findAll();
        $this->container = $container;
        $this->seoServices = $seoServices;
        $this->requestStack = $requestStack;
        $this->fileServices = $fileServices;
        $this->materialsCategoriesServices = $materialsCategoriesServices;
    }

    /**
     * Получение данных
     *
     * @return [type]
     */
    public function getServiceValue($param)
    {
        return $this->params->get($param) ?? null;
    }

    /**
     * Конвертация массива в строку
     *
     * @param mixed $array
     *
     * @return [type]
     */
    public function arrayToString($array)
    {
        return json_encode($array);
    }

    /**
     * Получение SEO данных
     *
     * @return [type]
     */
    public function getSeo()
    {
        $path = $this->container->get('router')->getContext()->getPathInfo();
        return $this->seoServices->getSeoByPath($path);
    }

    /**
     * Возвращаем, авторизован пользователь или нет
     *
     * @return bool
     */
    public function isAuth(): bool
    {
        $user = $this->security->getUser();

        return !empty($user);
    }

    /**
     * Преобразовываем к общему виду информацию о юзере
     *
     * @param User $user
     * @return array
     */
    public function parseUserEntity(User $user): array
    {
        return $this->userServices->getInformation($user);
    }

    /**
     * Возвращаем информацию о пользователе
     *
     * @return array|null
     */
    public function getUser(): ?array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return null;
        }

        return $this->userServices->getInformation($user);
    }

    /**
     * Форматируем номер телефона к единому виду
     *
     * @param string $phone
     * @return string
     */
    public function phone(string $phone): string
    {
        $phone = trim($phone);

        $res = preg_replace(
            array(
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
                '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',
            ),
            array(
                '+7 ($2) $3-$4-$5',
                '+7 ($2) $3-$4-$5',
                '+7 ($2) $3-$4-$5',
                '+7 ($2) $3-$4-$5',
                '+7 ($2) $3-$4',
                '+7 ($2) $3-$4',
            ),
            $phone
        );

        return $res;
    }

    /**
     * Склонение существительных после числительных.
     *
     * @param string $value Значение
     * @param array $words Массив вариантов, например: array('товар', 'товара', 'товаров')
     * @param bool $show Включает значение $value в результирующею строку
     *
     * @return [type]
     */
    public static function plural($value, $words, $show = true)
    {
        $num = $value % 100;
        if ($num > 19) {
            $num = $num % 10;
        }

        $out = ($show) ? $value . ' ' : '';
        switch ($num) {
            case 1:
                $out .= $words[0];
                break;
            case 2:
            case 3:
            case 4:
                $out .= $words[1];
                break;
            default:
                $out .= $words[2];
                break;
        }

        return $out;
    }

    /**
     * Возвращаем версию деплоя
     *
     * @return array|bool|float|int|string|null
     */
    public function getVersionDeploy()
    {
        return $this->params->get('base.version_deploy');
    }

    /**
     * Возвращает параметр сайта по коду
     *
     * @param mixed $code
     *
     * @return [type]
     */
    public function getSiteSettingByCode($code)
    {
        foreach ($this->siteSettings as $param) {
            if ($param->getCode() == $code) {
                return [
                    'id' => $param->getId(),
                    'name' => $param->getName(),
                    'code' => $param->getCode(),
                    'value' => $param->getValue()
                ];
            }
        }

        return [
            'id' => null,
            'name' => null,
            'code' => null,
            'value' => null
        ];
    }

    /**
     * Возвращает параметры сайта
     *
     * @return [type]
     */
    public function getSiteSettingsAll()
    {
        $result = [];
        foreach ($this->siteSettings as $param) {
            $result[] = [
                'id' => $param->getId(),
                'name' => $param->getName(),
                'code' => $param->getCode(),
                'value' => $param->getValue()
            ];
        }

        if (empty($result)) {
            return [
                'id' => null,
                'name' => null,
                'code' => null,
                'value' => null
            ];
        }

        return $result;
    }


    /**
     * Возвращаем идентификатор метрики
     *
     * @return string|null
     */
    public function getMetrikaId(): ?string
    {
        return $this->params->get('site.metrika_id') ?? null;
    }

    /**
     * Возвращает информацию по массиву
     *
     * @param $array
     * @return string|true
     */
    public function printR($array)
    {
        return print_r($array, true);
    }

    /**
     * Возвращает массив с числами
     *
     * @param int $digit
     * @return array
     */
    public function fillArrayByNum(int $digit): array
    {
        $result = [];

        for ($i = 0; $i < $digit; $i++) {
            $result[] = $i;
        }

        return $result;
    }

    /**
     * Возвращаем уникальные значения массива
     *
     * @param $items
     * @return array
     */
    public function getUniqueArrayServices(array $items): array
    {
        $result = [];

        foreach ($items as $val) {
            $result[$val['service']['icon']][] = $val['service']['name'];
        }

        return $result;
    }

    /**
     * Сортируем массив услуг
     *
     * @param array $items
     * @param $service_id
     * @return array
     */
    public function getSortedServices(array $items, $service_id = null): array
    {
        $items = array_map(function ($v) use ($service_id) {
            $v['sort_id'] = $v['service']['id'] == $service_id ? 1 : 0;
            return $v;
        }, $items);

        usort($items, function ($a, $b) {
            return $b['sort_id'] - $a['sort_id'];
        });

        return $items;
    }

    /**
     * Возвращаем красивую дату в виде строки
     *
     * @param int $unixtime
     * @param string $unixtime
     * @return string
     */
    public function getPrettyDate(int $unixtime, string $format = 'pretty'): string
    {
        $monthes = [
            'января', 'февраля',
            'марта', 'апреля',
            'мая', 'июня',
            'июля', 'августа',
            'сентября', 'октября',
            'ноября', 'декабря',
        ];

        if ($format == 'compact') {
            return date('d.m.Y', $unixtime);
        }

        $month = intval(date('m', $unixtime)) - 1;
        return date('d', $unixtime) . '&nbsp;' . $monthes[$month] . '&nbsp;' . date('Y', $unixtime) . ' г.';
    }



    /**
     * Генерация урла для пагинации (меняем только параметр page, остальные оставляем как есть)
     *
     * @param string $path
     * @param int $page
     * @param bool $auto
     * @return string
     */
    public function pagination(string $path, int $page, bool $auto): string
    {
        if (!$auto) {
            return $path . $page;
        }

        $decode_url = $this->getArrayArgs();

        if (!empty($page) && $page > 1) {
            $decode_url['page'] = $page;
        } else {
            unset($decode_url['page']);
        }

        return $path . (!empty($decode_url) ? '?' . http_build_query($decode_url) : '');
    }

    /**
     * Генерация урла для сортировки в соответствии с текущим адресом и запросом (меняем только параметр order и sort)
     *
     * @param $path
     * @param $item
     * @param string $order
     * @return array
     */
    public function sortItems($path, $item, $order = 'desc'): array
    {
        $decode_url = $this->getArrayArgs();

        if (empty($decode_url['sort'])) {
            $decode_url['sort'] = 'id';
            $decode_url['order'] = 'desc';
        }

        $selected = isset($decode_url['sort']) && $decode_url['sort'] == $item;

        if (isset($decode_url['order']) && $selected) {
            if ($decode_url['order'] == 'desc') {
                $decode_url['order'] = 'asc';
            } else {
                $decode_url['order'] = 'desc';
            }
        } else {
            $decode_url['order'] = $order;
        }

        $decode_url['sort'] = $item;

        unset($decode_url['page']);

        return [
            'selected' => $selected,
            'order' => $decode_url['order'],
            'url' => $path . '?' . http_build_query($decode_url)
        ];
    }

    /**
     * Стили для админ панели в таблице
     *
     * @param array $data
     * @param string $column
     *
     * @return string
     */
    public function getAdminTableStyles(array $data, string $column): string
    {
        return 'width: ' . $data[$column] . '; min-width: ' . $data[$column] . ';';
    }

    /**
     * URL сайта
     *
     * @return [type]
     */
    public function getSiteUrl()
    {
        return $this->params->get('base.url');
    }

    /**
     * Возвращает url без определенного параметра
     *
     * @return [type]
     */
    public function getUrlWithoutParam($skip_param)
    {
        $http = parse_url($this->requestStack->getCurrentRequest()->getRequestUri());
        if (isset($http['query'])) {
            parse_str($http['query'], $output);
            if (isset($output[$skip_param])) {
                unset($output[$skip_param]);
            }
            return $http["path"] . '?' . http_build_query($output);
        }

        return $this->requestStack->getCurrentRequest()->getRequestUri();
    }

    /**
     * Форматирование числа в строку для отображения цены.
     *
     * @return string
     */
    public function formatPrice($price)
    {
        return number_format($price, 0, '.', ' ');
    }

    /**
     * Информация о параметрах файлов (размер, кол-во пикселей)
     *
     *
     * @return [type]
     */
    public function getFilesSettings()
    {
        $result = [];
        $types = $this->fileServices->getAvailableTypes();
        if (!$types) {
            return [];
        }

        foreach ($types as $file_type) {
            if (!array_key_exists($file_type, FileServices::MAX_SIZES)) {
                return [];
            }

            $result[$file_type]['sizes'] = FileServices::MAX_SIZES[$file_type];
            if (array_key_exists($file_type, FileServices::MAX_SIZES_PIXEL)) {
                $result[$file_type]['pixels'] = FileServices::MAX_SIZES_PIXEL[$file_type];
            }
        }

        return $result;
    }

    /**
     * Преобразовать BBCode в HTML.
     *
     * @param String $tmp - Исходный шаблон.
     * @return String - HTML код.
     *
     * @return [type]
     */
    public function bbCodeToHtml($tmp)
    {
        foreach ($this->tokens as $token) {
            $tmp = preg_replace($token['token'], $token['replacement'], $tmp);
        }

        return $tmp;
    }

    /**
     * @return [type]
     */
    public function getCategories()
    {
        $user = $this->security->getUser();
        $categories = $this->materialsCategoriesServices->getMenuCategories($user);

        return $categories;
    }

    /**
     * Возвращает аргументы текущего запроса в виде массива
     *
     * @return array
     */
    private function getArrayArgs()
    {
        $decode_url = [];
        $args = $_SERVER['QUERY_STRING'];

        parse_str($args, $decode_url);

        return $decode_url;
    }
}
