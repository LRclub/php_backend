<?php

namespace App\Controller\Base;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class BaseApiController extends AbstractController
{
    protected const SORT_ASC = 'asc';
    protected const SORT_DESC = 'desc';

    /**
     * Разбираем тело запроса JSON
     * @param string $param
     * @return mixed
     */
    protected function getJson(Request $request, string $param = '')
    {
        $jsonBody = json_decode($request->getContent(), true);

        if ($param && isset($jsonBody[$param])) {
            return $jsonBody[$param];
        } elseif (!isset($jsonBody[$param])) {
            return null;
        } else {
            return $jsonBody;
        }
    }

    /**
     * Отправка сообщения об ошибке
     * @param $error
     * @param int $http_code
     * @param null $application_code
     * @return JsonResponse
     */
    protected function jsonError($error, $http_code = 400, $application_code = null)
    {
        $output = [
            'status' => false,
            'error' => $error,
        ];

        if ($application_code) {
            $output['code'] = $application_code;
        }

        return new JsonResponse($output, $http_code);
    }

    /**
     * Отправка успешного результата
     * @param $result
     * @return JsonResponse
     */
    protected function jsonSuccess($result = [])
    {
        $result = array_merge(['status' => true], $result);

        return new JsonResponse($result, 200);
    }

    /**
     * Возвращаем сообщение об ошибках по форме и сущности
     * @param FormInterface $form
     * @param bool $is_array
     * @return array|string
     */
    protected function getErrorMessages(FormInterface $form, $is_array = true)
    {
        $errors = array();

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'] = $error->getMessage();
            } else {
                if ($is_array) {
                    $errors[$key] = $error->getMessage();
                } else {
                    return $error->getMessage();
                }
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child, false);
            }
        }

        return $errors;
    }


    /**
     * Возвращаем сущность в виде гномика (массива)
     *
     * @param SerializerInterface $serializer
     * @param object $entity
     * @return mixed
     */
    protected function getEntityAsArray(SerializerInterface $serializer, object $entity)
    {
        return json_decode($serializer->serialize($entity, 'json'), true);
    }

    /**
     * Возвращаем ошибку по обработке формы
     *
     * @param FormInterface $form
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function formValidationError(FormInterface $form)
    {
        $errors = $this->getErrorMessages($form);

        return $this->jsonError($errors);
    }

    /**
     * Формирование формы на базе массива
     *
     * @param string $formType
     * @param array $data
     * @param null $entity
     * @return FormInterface
     */
    protected function createFormByArray(string $formType, array $data, $entity = null): FormInterface
    {
        $form = $this->container->get('form.factory')->create($formType, $entity);
        $form->submit($data);

        return $form;
    }

    /**
     * Возращает int значение или null (0 = null)
     *
     * @param mixed $value
     *
     * @return [type]
     */
    protected function getIntOrNull($value)
    {
        $int_value = intval($value);
        return $int_value ? $int_value : null;
    }
}
