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

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class MainMenu extends MenuItem
{
    /**
     * MainMenu constructor.
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param FactoryInterface $factory
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, FactoryInterface $factory)
    {
        parent::__construct('root', $factory);

        try {
            if($authorizationChecker->isGranted('ROLE_ADMIN')) {
                $this->addChild('admin_apiusers', [
                    'label' => 'app.menu.apiusers',
                    'route' => 'admin_get_apiusers',
                ]);
            }
        } catch(AuthenticationCredentialsNotFoundException $ex) {
            // Thrown when there is no token (on error pages)
        }

    }

}