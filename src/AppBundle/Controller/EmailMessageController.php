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

namespace AppBundle\Controller;

use AppBundle\Entity\Email\Message;
use AppBundle\Form\Email\MessageType;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @View
 * @RouteResource("Message")
 */
class EmailMessageController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(Message::class)
            ->createQueryBuilder('m')
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(Message $message)
    {
        $this->denyAccessUnlessGranted('VIEW', $message);
        return $message;
    }

    public function newAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->createForm(MessageType::class, new Message(), [
            'method' => 'POST',
            'action' => $this->generateUrl('post_message'),
        ])->add('submit', SubmitType::class, ['label' => 'form.submit']);
    }

    /**
     * @View("AppBundle:EmailMessage:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->newAction();
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_message', ['message' => $form->getData()->getId()]);
        }
        return $form;
    }

    public function editAction(Message $message)
    {
        $this->denyAccessUnlessGranted('EDIT', $message);
        return $this->createForm(MessageType::class, $message, [
            'method' => 'PUT',
            'action' => $this->generateUrl('put_message', ['message' => $message->getId()]),
        ])->add('submit', SubmitType::class, ['label' => 'form.submit']);
    }

    /**
     * @View("AppBundle:EmailMessage:edit.html.twig")
     */
    public function putAction(Request $request, Message $message)
    {
        $form = $this->editAction($message);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_message', ['message' => $message->getId()]);
        }
        return $form;
    }

    /**
     * @Route(methods={"GET", "POST"})
     */
    public function queueAction(Request $request, Message $message)
    {
        $this->denyAccessUnlessGranted('EDIT', $message);
        if($message->isQueued())
            throw $this->createAccessDeniedException('Message is already in the send queue.');
        $message->setScheduledSendTime(new \DateTime());
        $form = $this->createFormBuilder($message)
            ->add('scheduledSendTime', DateTimeType::class, [
                'label' => 'label.scheduledSendTime',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.submit'])
            ->getForm();
        $form->handleRequest($request);

        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_message', ['message' => $message->getId()]);
        }
        return $form;
    }

    public function removeAction(Message $message)
    {
        $this->denyAccessUnlessGranted('DELETE', $message);
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.emailMessage.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('delete_message', ['message' => $message->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:EmailMessage:remove.html.twig")
     */
    public function deleteAction(Request $request, Message $message)
    {
        $form = $this->removeAction($message);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->remove($message);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_messages');
        }
        return $form;
    }
}
