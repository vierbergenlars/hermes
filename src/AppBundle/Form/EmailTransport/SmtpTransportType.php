<?php

namespace AppBundle\Form\EmailTransport;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmtpTransportType extends EmailTransportType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('host', TextType::class, [
                'label' => 'label.url.hostname',
            ])
            ->add('port', IntegerType::class, [
                'label' => 'label.url.port',
            ])
            ->add('security', ChoiceType::class, [
                'label' => 'label.security',
                'choices_as_values' => true,
                'choices' => [
                    'label.security.none' => null,
                    'label.security.tls' => 'tls',
                    'label.security.ssl' => 'ssl',
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'label.username',
            ])
            ->add('password', TextType::class, [
                'label' => 'label.password',
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\EmailTransport\SmtpTransport'
        ));
    }
}
