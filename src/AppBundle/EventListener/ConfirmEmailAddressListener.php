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

use AppBundle\Entity\EmailAddress;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfirmEmailAddressListener implements EventSubscriber
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ConfirmEmailAddressListener constructor.
     * @param \Swift_Mailer $mailer
     * @param UrlGeneratorInterface $urlGenerator
     * @param RequestStack $requestStack
     */
    public function __construct(\Swift_Mailer $mailer, UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postUpdate,
            Events::postPersist,
        ];
    }

    public function postUpdate(LifecycleEventArgs $event)
    {
        $this->postPersist($event);
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        // TODO: Replace with our internal mail queue
        $entity = $event->getEntity();
        if($entity instanceof EmailAddress) {
            if($entity->getAuthCode()) {
                $domain = $this->requestStack->getMasterRequest()->getHost();
                $confirmationUrl = $this->urlGenerator->generate('confirm_emailaddress', [
                    'emailAddress' => $entity->getId(),
                    'authCode' => $entity->getAuthCode(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $message = \Swift_Message::newInstance('Authorize '.$domain.' to send emails on your behalf?')
                    ->setFrom('hermes@'.$domain, $domain. ' Hermes')
                    ->setTo($entity->getEmail(), $entity->getName())
                    ->setBody(<<<HTML
<p>Hi {$entity->getName()},</p>
<p>A user of the email application at <a href="http://{$domain}/">{$domain}</a> has requested to send emails on your behalf.<br>
Please visit <a href="{$confirmationUrl}">{$confirmationUrl}</a> to confirm this request, and  authorize the application to
send emails from {$entity->getEmail()}.
</p>

<p>If you do not want to authorize this request, you can ignore this email.</p>

<p>Sincerily,<br>
{$domain}</p>
HTML
                        ,'text/html')
                    ->addPart(<<<TEXT
Hi {$entity->getName()},

A user of the email application at {$domain} has requested to send emails on your behalf.
Please visit {$confirmationUrl} to confirm this request, and authorize the application to send emails from {$entity->getEmail()}.

If you do not want to authorize this request, you can ignore this email.

Sincerily,

{$domain}
TEXT
                        ,'text/plain')
                ;

                $this->mailer->send($message);
            }

        }

    }
}
