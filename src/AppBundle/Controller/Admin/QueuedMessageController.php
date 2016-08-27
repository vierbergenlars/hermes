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

namespace AppBundle\Controller\Admin;

use AppBundle\Controller\BaseController;
use AppBundle\Entity\Email\QueuedMessage;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @View
 * @RouteResource("Queuedmessage")
 * @Security("has_role('ROLE_ADMIN')")
 */
class QueuedMessageController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:Email\QueuedMessage')
            ->createQueryBuilder('m')
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(QueuedMessage $message)
    {
        return $message;
    }

    public function removeAction(QueuedMessage $message)
    {
        if($message->isSent())
            throw $this->createAccessDeniedException('Message has already been sent.');
        if($message->isFailed())
            throw $this->createAccessDeniedException('Message is already failed.');
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.queuedmessage.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('admin_delete_queuedmessage', ['message' => $message->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:Admin/QueuedMessage:remove.html.twig")
     */
    public function deleteAction(Request $request, QueuedMessage $message)
    {
        $form = $this->removeAction($message);
        $form->handleRequest($request);
        if($form->isValid()) {
            $message->setFailedAt(new \DateTime());
            if($message->getSourceRecipient()) {
                $message->getSourceRecipient()->setFailedTime(new \DateTime());
                $message->getSourceRecipient()->setFailureMessage(sprintf('Cancelled by user: %s', $this->getUser()->getUsername()));
            }
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('admin_get_queuedmessages');
        }
        return $form;
    }
}
