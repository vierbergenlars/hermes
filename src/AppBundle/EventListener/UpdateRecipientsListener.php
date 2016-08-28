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

use AppBundle\Entity\Email\Recipient;
use AppBundle\Event\UpdateRecipientsEvent;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateRecipientsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            UpdateRecipientsEvent::EVENT_NAME => 'updateRecipients',
        ];
    }

    public function updateRecipients(UpdateRecipientsEvent $event, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        $recipient = $event->getRecipient();
        $originatingRecipient = $recipient->getOriginatingRecipient();
        if($originatingRecipient&&$recipient->isFailed()) {
            $originatingRecipient->setFailedTime($recipient->getFailedTime());
            $failureMessage = $originatingRecipient->getFailureMessage();
            if($failureMessage === null)
                $failureMessage = "Failed children:\n";
            $failureMessage.= sprintf('%s: %s', (string)$recipient, $recipient->getFailureMessage());
            $originatingRecipient->setFailureMessage($failureMessage);
        }

        if(!$recipient->isSent()) {
            $unsentRecipients = $recipient->getChildRecipients()
                ->filter(function(Recipient $recipient) {
                    return !$recipient->isSent();
                });
            if($unsentRecipients->count() === 0)
                $recipient->setSentTime(new \DateTime());
        }
        if($originatingRecipient)
            $eventDispatcher->dispatch($eventName, new UpdateRecipientsEvent($originatingRecipient));

    }
}

