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

namespace AppBundle\Form\FilteredEntityType;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UseGrantedOnlyFilteredEntityType extends EntityType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(ManagerRegistry $registry, AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($registry);
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getLoader(ObjectManager $manager, $queryBuilder, $class)
    {
        return new FilteredORMQueryBuilderLoader($queryBuilder, function($object) {
            return $this->authorizationChecker->isGranted('USE', $object);
        });
    }
}

