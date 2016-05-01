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

use AppBundle\Controller\BaseController;
use AppBundle\Entity\EmailAddress;
use AppBundle\Form\EmailAddressType;
use FOS\RestBundle\Controller\Annotations\Get;
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
 * @RouteResource("Emailaddress")
 */
class EmailAddressController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:EmailAddress')
            ->createQueryBuilder('e')
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(EmailAddress $emailAddress)
    {
        $this->denyAccessUnlessGranted('VIEW', $emailAddress);
        return $emailAddress;
    }

    /**
     * @return mixed
     */
    public function newAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->createForm(EmailAddressType::class, new EmailAddress(), [
            'method' => 'POST',
            'action' => $this->generateUrl('post_emailaddress'),
        ]);
    }

    /**
     * @View("AppBundle:EmailAddress:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->newAction();

        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();
            $acl = $this->getAclProvider()->createAcl(ObjectIdentity::fromDomainObject($form->getData()));
            $acl->insertObjectAce(UserSecurityIdentity::fromAccount($this->getUser()), MaskBuilder::MASK_MASTER);
            $acl->insertObjectAce(new RoleSecurityIdentity('ROLE_ADMIN'), MaskBuilder::MASK_OWNER);
            $this->getAclProvider()->updateAcl($acl);

            return $this->redirectToRoute('get_emailaddress', ['emailAddress' => $form->getData()->getId()]);
        }
        return $form;
    }

    public function editAction(EmailAddress $emailAddress)
    {
        $this->denyAccessUnlessGranted('EDIT', $emailAddress);
        return $this->createForm(EmailAddressType::class, $emailAddress, [
            'method' => 'PUT',
            'action' => $this->generateUrl('put_emailaddress', ['emailAddress' => $emailAddress->getId()]),
        ]);
    }

    /**
     * @View("AppBundle:EmailAddress:edit.html.twig")
     */
    public function putAction(Request $request, EmailAddress $emailAddress)
    {
        $form = $this->editAction($emailAddress);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailaddress', ['emailAddress' => $emailAddress->getId()]);
        }
        return $form;
    }

    /**
     * @Get("emailaddresses/{emailAddress}/confirm/{authCode}")
     */
    public function confirmAction(EmailAddress $emailAddress, $authCode)
    {
        if($emailAddress->confirmAuthCode($authCode)) {
            $this->addFlash('success', 'flash.emailAddress.confirmation.success');
            $this->getEntityManager()->flush();
        } else {
            $this->addFlash('error', 'flash.emailAddress.confirmation.failure');
        }
        if($this->isGranted('VIEW', $emailAddress))
            return $this->redirectToRoute('get_emailaddress', ['emailAddress'=>$emailAddress->getId()]);
        return [];
    }

    public function removeAction(EmailAddress $emailAddress)
    {
        $this->denyAccessUnlessGranted('DELETE', $emailAddress);
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.emailAddress.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('delete_emailaddress', ['emailAddress' => $emailAddress->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:EmailAddress:remove.html.twig")
     */
    public function deleteAction(Request $request, EmailAddress $emailAddress)
    {
        $form = $this->removeAction($emailAddress);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getAclProvider()->deleteAcl(ObjectIdentity::fromDomainObject($emailAddress));
            $this->getEntityManager()->remove($emailAddress);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailaddresses');
        }
        return $form;
    }
}
