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
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct('root', $factory);

        $this->addChild('messages', [
            'label' => 'app.menu.messages',
            'route' => 'get_messages',
        ]);
        $this->addChild('new_message', [
            'label' => 'app.menu.messages.new',
            'route' => 'new_message',
        ]);

        if($authorizationChecker->isGranted('ROLE_ADMIN')) {
            $this->addChild('queued_messages', [
                'label' => 'app.menu.queuedmessages',
                'route' => 'admin_get_queuedmessages',
            ]);
        }

        $config = $this->addChild('config', [
            'label' => 'app.menu.config'
        ]);
        $config->addChild('emailaddresses', [
            'label' => 'app.menu.emailaddresses',
            'route' => 'get_emailaddresses',
        ]);
        $config->addChild('emailtemplates', [
            'label' => 'app.menu.emailtemplates',
            'route' => 'get_emailtemplates',
        ]);
        if($authorizationChecker->isGranted('ROLE_ADMIN')) {
            $config->addChild('admin_apiusers', [
                'label' => 'app.menu.apiusers',
                'route' => 'admin_get_apiusers',
            ]);
        }
    }

}
