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

use AppBundle\Entity\Email\AuthserverRecipient;
use AppBundle\Entity\Email\GroupRecipient;
use AppBundle\Entity\Email\Message;
use AppBundle\Entity\Email\Recipient;
use AppBundle\Entity\Email\StandardRecipient;
use AppBundle\Form\Email\AuthserverRecipientType;
use AppBundle\Form\Email\GroupRecipientType;
use AppBundle\Form\Email\MessageType;
use AppBundle\Form\Email\StandardRecipientType;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

/**
 * @View
 * @RouteResource("Recipient")
 */
class EmailMessageRecipientController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request, Message $message)
    {
        $this->denyAccessUnlessGranted('VIEW', $message);
        $queryBuilder = $this->getEntityManager()
            ->getRepository(Recipient::class)
            ->createQueryBuilder('r')
            ->where('r.message = :message')
            ->andWhere('r.originatingRecipient IS NULL')
            ->setParameter('message', $message)
        ;
        return $this->paginate($queryBuilder, $request);
    }

    /**
     * @Get
     * @View(template="AppBundle:EmailMessageRecipient:cget.html.twig")
     */
    public function cgetChildrenAction(Request $request, Message $message, Recipient $recipient)
    {
        $this->denyAccessUnlessGranted('VIEW', $message);
        $queryBuilder = $this->getEntityManager()
            ->getRepository(Recipient::class)
            ->createQueryBuilder('r')
            ->where('r.message = :message')
            ->andWhere('r.originatingRecipient = :recipient')
            ->setParameter('message', $message)
            ->setParameter('recipient', $recipient)
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(Message $message, Recipient $recipient)
    {
        $this->denyAccessUnlessGranted('VIEW', $message);
        return $recipient;
    }

    private function createNewForms(Message $message) {
        $forms = [
            'standardRecipient' => [StandardRecipientType::class, new StandardRecipient($message)],
            'authserverRecipient' => [AuthserverRecipientType::class, new AuthserverRecipient($message)],
            'groupRecipient' => [GroupRecipientType::class, new GroupRecipient($message)],
        ];

        return array_map(function($data) use($message) {
            return $this->createForm($data[0], $data[1], [
                'method' => 'POST',
                'action' => $this->generateUrl('post_message_recipient', ['message'=>$message->getId()]),
            ])->add('submit', SubmitType::class, ['label' => 'form.submit']);
        }, $forms);
    }

    public function newAction(Message $message)
    {
        $this->denyAccessUnlessGranted('EDIT', $message);

        return ['forms'=>array_map(function(FormInterface $form) {
            return $form->createView();
        }, $this->createNewForms($message))];
    }

    /**
     * @View("AppBundle:EmailMessageRecipient:new.html.twig")
     */
    public function postAction(Request $request, Message $message)
    {
        $forms = $this->createNewForms($message);

        foreach($forms as $form) {
            /* @var $form FormInterface */
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getEntityManager()->persist($form->getData());
                $this->getEntityManager()->flush();

                return $this->redirectToRoute('get_message_recipient', ['message' => $message->getId(), 'recipient' => $form->getData()->getId()]);
            }
        }

        return ['forms'=>array_map(function(FormInterface $form) {
            return $form->createView();
        }, $forms)];
    }

    public function removeAction(Message $message, Recipient $recipient)
    {
        $this->denyAccessUnlessGranted('EDIT', $message);
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.emailMessageRecipient.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('delete_message_recipient', ['message' => $message->getId(), 'recipient'=>$recipient->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:EmailMessageRecipient:remove.html.twig")
     */
    public function deleteAction(Request $request, Message $message, Recipient $recipient)
    {
        $form = $this->removeAction($message, $recipient);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->remove($recipient);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_message_recipients', ['message'=>$message->getId()]);
        }
        return $form;
    }
}
