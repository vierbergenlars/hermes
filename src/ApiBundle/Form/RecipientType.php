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

use AppBundle\Form\Email\AuthserverRecipientType;
use AppBundle\Form\Email\GroupRecipientType;
use AppBundle\Form\Email\StandardRecipientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class RecipientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('standard', StandardRecipientType::class, [
                'required' => false,
            ])
            ->add('authserver', AuthserverRecipientType::class, [
                'required' => false,
            ])
            ->add('group', GroupRecipientType::class, [
                'required' => false,
            ])
        ;

        $builder->addModelTransformer(new RecipientTransformer());

    }
}
