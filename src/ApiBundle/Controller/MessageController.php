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

namespace ApiBundle\Controller;

use AppBundle\Entity\Email\Message;
use AppBundle\Entity\EmailAddress;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\DeserializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @View(serializerGroups={"Default", "message"})
 */
class MessageController extends BaseController implements ClassResourceInterface
{
    /**
     * @Security("is_granted('VIEW', message)")
     * @View(serializerGroups={"Default", "message", "message_object"})
     */
    public function getAction(Message $message)
    {
        return $message;
    }

    /**
     * @param Request $request
     * @return \FOS\RestBundle\View\View|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        $deserializationContext = DeserializationContext::create()
            ->setGroups(['Default', 'message', 'message_object', 'message_POST']);
        $em = $this->getEntityManager();
        $message = $this->deserializeRequest($request, Message::class, $deserializationContext, function(Message $message, ConstraintViolationListInterface $errors) use ($em) {
            if($message->getSender()) {
                $sender = $em->getRepository(EmailAddress::class)->findOneBy([
                    'email' => $message->getSender()->getEmail(),
                    'authCode' => null,
                ]);
                if(!$sender) {
                    $errors->add(new ConstraintViolation(
                        'Sender does not exist: '.$message->getSender()->getEmail(),
                        'Sender does not exist: {sender}',
                        ['sender' => $message->getSender()->getEmail()],
                        $message,
                        'sender',
                        null
                    ));
                } else {
                    if(!$this->isGranted('USE', $sender)) {
                        $errors->add(new ConstraintViolation(
                            'Permission denied to use sender: ' . $sender->getEmail(),
                            'Permission denied to use sender: {sender}',
                            ['sender' => $sender->getEmail()],
                            $message,
                            'sender',
                            null
                        ));
                    } else {
                        $message->setSender($sender);
                    }
                }
            }
            if($message->getTemplate()) {
                $message->getTemplate()->setName('__inline__'.base_convert(bin2hex(openssl_random_pseudo_bytes(32)), 16, 36));
                if($message->getTemplate()->getLocalizedTemplates())
                    foreach($message->getTemplate()->getLocalizedTemplates() as $localizedTemplate)
                        $localizedTemplate->setTemplate($message->getTemplate());
            }
            if(!$message->getPriority())
                $message->setPriority(1);
        });
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
        return $this->routeRedirectView('api_get_message', ['message' => $message->getId()]);
    }

    /**
     * @Security("is_granted('EDIT', message)")
     */
    public function patchAction(Request $request, Message $message)
    {
        if($message->getScheduledSendTime())
            throw new ConflictHttpException('Message is already scheduled to be sent.');
        $deserializationContext = DeserializationContext::create()
            ->setGroups(['message_send']);
        $this->deserializeRequest($request, Message::class, $deserializationContext, function(Message $partialMessage) use($message) {
            if($partialMessage->getPriority())
                $message->setPriority($partialMessage->getPriority());
            if($partialMessage->getScheduledSendTime())
                $message->setScheduledSendTime($partialMessage->getScheduledSendTime());
            return $message;
        });

        $this->getEntityManager()->flush();
    }

    /**
     * @Security("is_granted('DELETE', message)")
     */
    public function deleteAction(Message $message)
    {
        $this->getEntityManager()->remove($message);
        $this->getEntityManager()->flush();
    }

}
