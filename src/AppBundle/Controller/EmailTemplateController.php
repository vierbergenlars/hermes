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

use AppBundle\Entity\EmailTemplate;
use AppBundle\Form\EmailTemplateType;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

/**
 * @View
 * @RouteResource("Emailtemplate")
 */
class EmailTemplateController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:EmailTemplate')
            ->createQueryBuilder('e')
            ->where('e.name NOT LIKE \'__inline__%\'')
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(EmailTemplate $emailTemplate)
    {
        $this->denyAccessUnlessGranted('VIEW', $emailTemplate);
        return $emailTemplate;
    }

    public function newAction(Request $request = null)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $emailTemplate = null;
        if($request&&$request->query->has('copy_from')) {
            $emailTemplate = $this->getEntityManager()
                ->getRepository('AppBundle:EmailTemplate')
                ->find($request->query->get('copy_from'));
        }

        return $this->createForm(EmailTemplateType::class, $emailTemplate, [
            'method' => 'POST',
            'action' => $this->generateUrl('post_emailtemplate'),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @View("AppBundle:EmailTemplate:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->newAction();

        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtemplate', ['emailTemplate' => $form->getData()->getId()]);
        }
        return $form;
    }

    public function editAction(EmailTemplate $emailTemplate)
    {
        $this->denyAccessUnlessGranted('EDIT', $emailTemplate);
        return $this->createForm(EmailTemplateType::class, $emailTemplate, [
            'method' => 'PUT',
            'action' => $this->generateUrl('put_emailtemplate', ['emailTemplate' => $emailTemplate->getId()]),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @View("AppBundle:EmailTemplate:edit.html.twig")
     */
    public function putAction(Request $request, EmailTemplate $emailTemplate)
    {
        $form = $this->editAction($emailTemplate);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtemplate', ['emailTemplate' => $emailTemplate->getId()]);
        }
        return $form;
    }

    public function removeAction(EmailTemplate $emailTemplate)
    {
        $this->denyAccessUnlessGranted('DELETE', $emailTemplate);
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.emailTemplate.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('delete_emailtemplate', ['emailTemplate' => $emailTemplate->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:EmailTemplate:remove.html.twig")
     */
    public function deleteAction(Request $request, EmailTemplate $emailTemplate)
    {
        $form = $this->removeAction($emailTemplate);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->remove($emailTemplate);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtemplates');
        }
        return $form;
    }
}
