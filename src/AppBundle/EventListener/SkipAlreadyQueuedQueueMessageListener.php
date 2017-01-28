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

namespace AppBundle\EventListener;

use AppBundle\Entity\Email\AuthserverRecipient;
use AppBundle\Entity\Email\GroupRecipient;
use AppBundle\Entity\Email\StandardRecipient;
use AppBundle\Event\QueueMessageEvent;
use AppBundle\Event\SendMessageEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use vierbergenlars\Authserver\Client\AuthserverAdminClient;
use vierbergenlars\Authserver\Client\Model\Group;
use vierbergenlars\Authserver\Client\Model\User;

class SkipAlreadyQueuedQueueMessageListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueueMessageEvent::EVENT_NAME => ['queueMessage', 100]
        ];
    }

    public function queueMessage(QueueMessageEvent $event, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        $recipient = $event->getRecipient();
        if($recipient->isQueued()||$recipient->isFailed()||$recipient->isSent())
            $event->stopPropagation();
    }
}

