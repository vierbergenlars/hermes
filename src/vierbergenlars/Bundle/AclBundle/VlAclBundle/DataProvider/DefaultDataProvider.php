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


namespace vierbergenlars\Bundle\AclBundle\VlAclBundle\DataProvider;


use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class DefaultDataProvider implements DataProviderInterface
{
    /**
     * @return array Array mapping the name of the mask to its value
     */
    public function getMasksMap()
    {
        return [
            'VIEW' => MaskBuilder::MASK_VIEW,
            'EDIT' => MaskBuilder::MASK_EDIT,
            'CREATE' => MaskBuilder::MASK_CREATE,
            'DELETE' => MaskBuilder::MASK_DELETE,
            'UNDELETE' => MaskBuilder::MASK_UNDELETE,
            'OPERATOR' => MaskBuilder::MASK_OPERATOR,
            'MASTER' => MaskBuilder::MASK_MASTER,
            'OWNER' => MaskBuilder::MASK_OWNER,
        ];
    }

    /**
     * @return array Array mapping the friendly name of a role to its raw value
     */
    public function getRoles()
    {
        return [
            'Admin' => 'ROLE_ADMIN',
            'User' => 'ROLE_USER',
        ];
    }

    /**
     * @return string The class of the user entity
     */
    public function getUserClass()
    {
        return 'AppBundle:User';
    }
}
