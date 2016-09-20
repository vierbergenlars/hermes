<?php
/**
 * Hermes, an HTTP-based templated mail sender for transactional and mass mailing.
 *
 * Copyright (C) 2016  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ApiBundle\Form;

use AppBundle\Entity\EmailAddress;
use AppBundle\Form\EmailTemplateType;
use AppBundle\Form\FilteredEntityType\UseGrantedOnlyFilteredEntityType;
use AppBundle\Form\LocalizedEmailTemplateType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlainMessageType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sender', UseGrantedOnlyFilteredEntityType::class, [
                'class' => EmailAddress::class,
                'query_builder' => function(EntityRepository $repository) {
                    return $repository->createQueryBuilder('e')
                        ->where('e.authCode IS NULL');
                },
                'choice_label' => function(EmailAddress $emailAddress) {
                    return sprintf('%s (%s)', $emailAddress->getName(), $emailAddress->getEmail());
                },
                'choice_value' => 'email',
            ])
            ->add('templates', CollectionType::class, [
                'property_path' => 'template.localizedTemplates',
                'entry_type' => LocalizedEmailTemplateType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('priority', IntegerType::class)
            ->add('recipients', CollectionType::class, [
                'entry_type' => RecipientType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('sendTime', DateTimeType::class, [
                'property_path' => 'scheduledSendTime',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
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
