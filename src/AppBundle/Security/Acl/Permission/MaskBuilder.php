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

namespace AppBundle\Security\Acl\Permission;


class MaskBuilder extends \Symfony\Component\Security\Acl\Permission\MaskBuilder
{
    const MASK_USE = 256;  // 1 << 8

    const CODE_USE = 'U';

    public static function getName($mask)
    {
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        $reflection = new \ReflectionClass(get_called_class());
        foreach ($reflection->getConstants() as $name => $cMask) {
            if (0 !== strpos($name, 'MASK_') || $mask !== $cMask) {
                continue;
            }

            if (!defined($cName = 'static::CODE_'.substr($name, 5))) {
                throw new \RuntimeException('There was no code defined for this mask.');
            }

            return substr($name, 5);
        }

        throw new \InvalidArgumentException(sprintf('The mask "%d" is not supported.', $mask));
    }
}
