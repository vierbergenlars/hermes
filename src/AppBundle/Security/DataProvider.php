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

namespace AppBundle\Security;

use AppBundle\Entity\ApiUser;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use vierbergenlars\Authserver\Client\Repository\GroupRepository;
use vierbergenlars\Bundle\AclBundle\VlAclBundle\DataProvider\DefaultDataProvider;

class DataProvider extends DefaultDataProvider
{
    /**
     * @var GroupRepository
     */
    private $groupRepo;

    /**
     * DataProvider constructor.
     *
     * @param GroupRepository $groupRepo
     */
    public function __construct(GroupRepository $groupRepo)
    {
        $this->groupRepo = $groupRepo;
    }

    public function getMasksMap()
    {
        return [
            'VIEW' => MaskBuilder::MASK_VIEW,
            'USE' => MaskBuilder::MASK_USE,
            'EDIT' => MaskBuilder::MASK_EDIT,
            'CREATE' => MaskBuilder::MASK_CREATE,
            'DELETE' => MaskBuilder::MASK_DELETE,
            'UNDELETE' => MaskBuilder::MASK_UNDELETE,
            'OPERATOR' => MaskBuilder::MASK_OPERATOR,
            'MASTER' => MaskBuilder::MASK_MASTER,
            'OWNER' => MaskBuilder::MASK_OWNER,
        ];
    }

    public function getRoles()
    {
        $roles = parent::getRoles();
        $groups = $this->groupRepo->findBy(['exportable'=>true]);
        foreach($groups as $group) {
            /* @var $group \vierbergenlars\Authserver\Client\Model\Group */
            $roles[$group->getDisplayName().' ('.$group->getName().')'] = 'ROLE_GROUP_'.strtoupper($group->getName());
        }
        return $roles;
    }

    public function getUserClass()
    {
        return ApiUser::class;
    }
}

