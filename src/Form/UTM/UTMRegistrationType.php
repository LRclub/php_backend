<?php

namespace App\Form\UTM;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Форма для проверки utm меток
 *
 * @package App\Form
 */
class UTMRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //по сути эта форма нужна, просто чтобы дернуть данные
        //валидацию здесь не делаем, иначе если завалится проверка, регистрация для пользователя будет заблокирована
        $builder
            ->add('utm_source', TextType::class)
            ->add('utm_medium', TextType::class)
            ->add('utm_campaign', TextType::class)
            ->add('utm_term', TextType::class)
            ->add('utm_content', TextType::class)
            ->add('time', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
