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

/**
 * Created by PhpStorm.
 * User: lars
 * Date: 9/7/16
 * Time: 6:14 PM
 */

namespace ApiBundle\Form;


use AppBundle\Entity\Email\AuthserverRecipient;
use AppBundle\Entity\Email\GroupRecipient;
use AppBundle\Entity\Email\StandardRecipient;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RecipientTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if(!$value)
            return [];
        switch(get_class($value)) {
            case StandardRecipient::class:
                return ['standard' => $value];
            case AuthserverRecipient::class:
                return ['authserver' => $value];
            case GroupRecipient::class:
                return ['group' => $value];
            default:
                throw new TransformationFailedException(sprintf('I don\'t know recipient type %s', get_class($value)));
        }
    }

    public function reverseTransform($value)
    {
        if(!$value)
            return null;
        foreach($value as $v) {
            if($v !== null)
                return $v;
        }
        return null;
    }
}
