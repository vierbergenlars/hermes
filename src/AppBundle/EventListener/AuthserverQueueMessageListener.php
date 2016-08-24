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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use vierbergenlars\Authserver\Client\AuthserverAdminClient;
use vierbergenlars\Authserver\Client\Model\Group;
use vierbergenlars\Authserver\Client\Model\User;

class AuthserverQueueMessageListener implements EventSubscriberInterface
{
    /**
     * @var AuthserverAdminClient
     */
    private $client;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(AuthserverAdminClient $client, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            'hermes.queue_message' => 'safeQueueMessage',
        ];
    }

    public function safeQueueMessage(QueueMessageEvent $event, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        try {
            $this->queueMessage($event, $eventName, $eventDispatcher);
        } catch(ContextErrorException $ex) {
            throw $ex;
        } catch(\Exception $ex) {
            $event->getRecipient()->setFailedTime(new \DateTime());
            $event->getRecipient()->setFailureMessage($ex->getMessage());
        }
    }

    public function queueMessage(QueueMessageEvent $event, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        $recipient = $event->getRecipient();
        if($recipient instanceof AuthserverRecipient) {
            $user = $this->client->getRepository(User::class)->find(strtoupper($recipient->getUserId()));
            /* @var $user User */

            $primaryEmail = $user->getEmails()->getPrimaryAddress();
            if(!$primaryEmail) {
                throw new \RuntimeException('User does not have a primary email address.');
            } elseif(!$primaryEmail->isVerified()) {
                throw new \RuntimeException('Email address is not verified.');
            } else {
                $stdRecipient = new StandardRecipient($event->getMessage());
                $stdRecipient->setName($user->getDisplayName());
                $stdRecipient->setEmailaddress($primaryEmail->getAddress());
                $stdRecipient->setOriginatingRecipient($recipient);
                $this->em->persist($stdRecipient);
                $newEvent = new QueueMessageEvent($event->getMessage(), $stdRecipient, $event->getExtraTemplateParameters()+['user'=>$user]);
                $eventDispatcher->dispatch($eventName, $newEvent);
                $recipient->setQueuedTime(new \DateTime());
            }
        } elseif($recipient instanceof GroupRecipient) {
            $group = $this->client->getRepository(Group::class)->find($recipient->getGroupName());
            /* @var $group Group */
            $recipients = array_map(function(User $user) use($event, $recipient) {
                $authserverRecipient = new AuthserverRecipient($event->getMessage());
                $authserverRecipient->setUserId($user->getGuid());
                $authserverRecipient->setOriginatingRecipient($recipient);
                $this->em->persist($authserverRecipient);
                return $authserverRecipient;
            }, iterator_to_array($group->getAllUsers()));

            foreach($recipients as $r) {
                /* @var $user User */
                $newEvent = new QueueMessageEvent($event->getMessage(), $r, $event->getExtraTemplateParameters());
                $eventDispatcher->dispatch($eventName, $newEvent);
            }

            $recipient->setQueuedTime(new \DateTime());
        }
    }
}

