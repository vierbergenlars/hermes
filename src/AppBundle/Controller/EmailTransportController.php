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

use AppBundle\Entity\EmailTransport\EmailTransport;
use AppBundle\Entity\EmailTransport\MailTransport;
use AppBundle\Entity\EmailTransport\SmtpTransport;
use AppBundle\Form\EmailTransport\MailTransportType;
use AppBundle\Form\EmailTransport\SmtpTransportType;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * @View
 * @RouteResource("Emailtransport")
 */
class EmailTransportController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(EmailTransport::class)
            ->createQueryBuilder('e')
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(EmailTransport $emailTransport)
    {
        $this->denyAccessUnlessGranted('VIEW', $emailTransport);
        return $emailTransport;
    }

    /**
     * @Get("emailtransports/new/mail")
     * @View("AppBundle:EmailTransport:new.html.twig")
     */
    public function newMailAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->createForm(MailTransportType::class, new MailTransport(), [
            'method' => 'POST',
            'action' => $this->generateUrl('post_emailtransport_mail'),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @Post("emailtransports/mail")
     * @View("AppBundle:EmailTransport:new.html.twig")
     */
    public function postMailAction(Request $request)
    {
        $form = $this->newMailAction();

        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtransport', ['emailTransport' => $form->getData()->getId()]);
        }
        return $form;
    }

    /**
     * @Get("emailtransports/new/smtp")
     * @View("AppBundle:EmailTransport:new.html.twig")
     */
    public function newSmtpAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->createForm(SmtpTransportType::class, new SmtpTransport(), [
            'method' => 'POST',
            'action' => $this->generateUrl('post_emailtransport_smtp'),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @Post("emailtransports/smtp")
     * @View("AppBundle:EmailTransport:new.html.twig")
     */
    public function postSmtpAction(Request $request)
    {
        $form = $this->newSmtpAction();

        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();
            $acl = $this->getAclProvider()->createAcl(ObjectIdentity::fromDomainObject($form->getData()));
            $acl->insertObjectAce(UserSecurityIdentity::fromAccount($this->getUser()), MaskBuilder::MASK_MASTER);
            $acl->insertObjectAce(new RoleSecurityIdentity('ROLE_ADMIN'), MaskBuilder::MASK_OWNER);
            $this->getAclProvider()->updateAcl($acl);

            return $this->redirectToRoute('get_emailtransport', ['emailTransport' => $form->getData()->getId()]);
        }
        return $form;
    }
    public function editAction(EmailTransport $emailTransport)
    {
        $this->denyAccessUnlessGranted('EDIT', $emailTransport);
        return $this->createForm($emailTransport::FORM_TYPE, $emailTransport, [
            'method' => 'PUT',
            'action' => $this->generateUrl('put_emailtransport', ['emailTransport' => $emailTransport->getId()]),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @View("AppBundle:EmailTransport:edit.html.twig")
     */
    public function putAction(Request $request, EmailTransport $emailTransport)
    {
        $form = $this->editAction($emailTransport);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtransport', ['emailTransport' => $emailTransport->getId()]);
        }
        return $form;
    }

    public function removeAction(EmailTransport $emailTransport)
    {
        $this->denyAccessUnlessGranted('DELETE', $emailTransport);
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.emailTransport.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('delete_emailtransport', ['emailTransport' => $emailTransport->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:EmailTransport:remove.html.twig")
     */
    public function deleteAction(Request $request, EmailTransport $emailTransport)
    {
        $form = $this->removeAction($emailTransport);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->remove($emailTransport);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtransports');
        }
        return $form;
    }
}
