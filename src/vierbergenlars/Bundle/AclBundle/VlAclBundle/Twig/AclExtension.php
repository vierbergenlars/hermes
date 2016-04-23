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

namespace vierbergenlars\Bundle\AclBundle\VlAclBundle\Twig;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;

class AclExtension extends \Twig_Extension
{
    /**
     * @var PermissionMapInterface
     */
    private $permissionMap;

    /**
     * AclExtension constructor.
     * @param PermissionMapInterface $permissionMap
     */
    public function __construct(PermissionMapInterface $permissionMap)
    {
        $this->permissionMap = $permissionMap;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'vl_acl_extension';
    }

    public function getFunctions()
    {
        return [
             new \Twig_SimpleFunction('doesAceGrantPermission', [$this, 'doesAceGrantPermission'])
        ];
    }

    public function doesAceGrantPermission(EntryInterface $ace, $permission) {
        $masks = $this->permissionMap->getMasks($permission, null);
        foreach($masks as $mask)
            if($ace->getMask()&$mask)
                return true;
        return false;
    }
}

