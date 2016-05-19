<?php

namespace AppBundle\Form\Email;

use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\EmailTransport\EmailTransport;
use AppBundle\Form\EmailTemplateType;
use AppBundle\Form\FilteredEntityType\UseGrantedOnlyFilteredEntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\VarDumper\VarDumper;

class MessageType extends AbstractType
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sender', UseGrantedOnlyFilteredEntityType::class, [
                'class' => EmailAddress::class,
                'choice_label' => function(EmailAddress $emailAddress) {
                    return sprintf('%s (%s)', $emailAddress->getName(), $emailAddress->getEmail());
                },
                'label' => 'label.sender',
            ])
            ->add('template', FormType::class, [
                'label' => 'label.template',
            ])
        ;
        $builder->get('template')
            ->add('opt_1', UseGrantedOnlyFilteredEntityType::class, [
                'required' => false,
                'label' => false,
                'placeholder' => 'label.template.create_new',
                'class' => EmailTemplate::class,
                'query_builder' => function(EntityRepository $repo) {
                    return $repo->createQueryBuilder('t')
                        ->where('t.name NOT LIKE \'__inline__%\'');
                },
                'choice_label' => 'name',
                'attr' => [
                    'onchange' => '$(this).parents(".form-group").eq(0).next().css("display", $(this).val()?"none":"block")',
                ],
            ])
            ->add('opt_2', EmailTemplateType::class, [
                'required' => false,
                'label' => false,
            ])
            ->addModelTransformer(new CallbackTransformer(function(EmailTemplate $template = null) {
                if($template && strncmp($template->getName(), '__inline__', 10) === 0)
                    return ['opt_1' => null, 'opt_2' => $template];
                return ['opt_1' => $template];
            }, function($data) {
                if($data['opt_1'])
                    return $data['opt_1'];
                return $data['opt_2'];
            }))
            ;
        $builder->get('template')->get('opt_2')->remove('name');
        $builder->get('template')->get('opt_2')->add('name', HiddenType::class, [
            'data' => '__inline__'.base_convert(bin2hex(openssl_random_pseudo_bytes(32)), 16, 36),
        ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Email\Message'
        ));
    }
}
