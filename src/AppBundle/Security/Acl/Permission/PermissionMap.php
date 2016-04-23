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

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

class PermissionMap extends BasicPermissionMap
{
    const PERMISSION_USE='USE';

    public function __construct()
    {
        parent::__construct();
        $this->map[self::PERMISSION_USE] = [
            MaskBuilder::MASK_USE,
            MaskBuilder::MASK_OPERATOR,
            MaskBuilder::MASK_MASTER,
            MaskBuilder::MASK_OWNER,
        ];
        $this->map[self::PERMISSION_VIEW][] = MaskBuilder::MASK_USE;
    }

    public function getMaskBuilder()
    {
        return new MaskBuilder();
    }
}
