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

use AppBundle\Entity\Email\QueuedMessage;
use AppBundle\Entity\Email\StandardRecipient;
use AppBundle\Entity\LocalizedEmailTemplate;
use AppBundle\Event\QueueMessageEvent;
use AppBundle\Event\SendMessageEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueueMessageListener implements EventSubscriberInterface
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * QueueMessageListener constructor.
     * @param \Twig_Environment $twig
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(\Twig_Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }


    public static function getSubscribedEvents()
    {
        return [
            QueueMessageEvent::EVENT_NAME => 'safeQueueMessage',
        ];
    }

    public function safeQueueMessage(QueueMessageEvent $event)
    {
        try {
            $this->queueMessage($event);
        } catch(ContextErrorException $ex) {
            throw $ex;
        } catch(\Exception $ex) {
            $event->getRecipient()->setFailedTime(new \DateTime());
            $event->getRecipient()->setFailureMessage($ex->getMessage());
        }
    }

    private function queueMessage(QueueMessageEvent $event)
    {
        $recipient = $event->getRecipient();
        if($recipient instanceof StandardRecipient) {
            $templateParameters = array_merge($event->getMessage()->getTemplateData(), $event->getExtraTemplateParameters());
            if(isset($templateParameters['_locale'])) {
                $localizedTemplate = $event->getMessage()->getTemplate()->getLocalizedTemplate($templateParameters['_locale']);
                /* @var $localizedTemplate LocalizedEmailTemplate */
                $subjectTemplate = $this->twig->createTemplate($localizedTemplate->getSubject());
                $bodyTemplate = $this->twig->createTemplate($localizedTemplate->getBody());
            } elseif($event->getMessage()->getTemplate()->getLocalizedTemplates()->count() > 1) {
                $subjectTemplate = $this->twig->createTemplate($event->getMessage()->getTemplate()->getLocalizedTemplate('en')->getSubject());
                $bodyTemplateStr = '';
                foreach ($event->getMessage()->getTemplate()->getLocalizedTemplates() as $localizedTemplate) {
                    /* @var $localizedTemplate \AppBundle\Entity\LocalizedEmailTemplate */
                    $bodyTemplateStr .= '===============' . $localizedTemplate->getLocale() . '==============' . "\r\n";
                    $bodyTemplateStr .= $localizedTemplate->getBody();
                }
                $bodyTemplate = $this->twig->createTemplate($bodyTemplateStr);
            } else {
                $localizedTemplate = $event->getMessage()->getTemplate()->getLocalizedTemplates()->first();
                /* @var $localizedTemplate LocalizedEmailTemplate */
                $subjectTemplate = $this->twig->createTemplate($localizedTemplate->getSubject());
                $bodyTemplate = $this->twig->createTemplate($localizedTemplate->getBody());
            }

            $subject = $subjectTemplate->render($templateParameters);
            $body = $bodyTemplate->render($templateParameters);

            $queuedMessage = new QueuedMessage();
            $queuedMessage
                ->setSender($event->getMessage()->getSender()->getEmail())
                ->setFromAddress($event->getMessage()->getSender()->getEmail())
                ->setFromName($event->getMessage()->getSender()->getName())
                ->setPriority($event->getMessage()->getPriority())
                ->setToAddress($recipient->getEmailaddress())
                ->setToName($recipient->getName())
                ->setSubject($subject)
                ->setBody($body)
                ->setSourceRecipient($recipient)
            ;
            $this->entityManager->persist($queuedMessage);
            $recipient->setQueuedTime(new \DateTime());
        }
    }
}

