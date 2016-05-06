<?php

namespace AppBundle\Form\EmailTransport;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class EmailTransportType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            ->add('deliveryStarttime', DateTimeType::class, [
                'label' => 'label.deliveryStarttime',
            ])
            ->add('deliveryPeriodDuration', TextType::class, [
                'label' => 'label.deliveryPeriodDuration',
            ])
            ->add('deliveryPeriodMaxmails', IntegerType::class, [
                'label' => 'label.deliveryPeriodMaxmails',
            ])
            ->add('deliveryLatency', TextType::class, [
                'label' => 'label.deliveryLatency',
            ])
        ;
        $dateIntervalTransformer = new CallbackTransformer(function ($data) {
            if($data instanceof \DateInterval)
                return $data->format("P%yY%mM%dDT%hH%iM%sS");
            return $data;
        }, function ($data) {
            return $data;
        });
        $builder->get('deliveryPeriodDuration')->addViewTransformer($dateIntervalTransformer);
        $builder->get('deliveryLatency')->addViewTransformer($dateIntervalTransformer);
    }
}
