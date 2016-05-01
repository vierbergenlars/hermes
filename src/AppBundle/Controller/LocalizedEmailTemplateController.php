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
use AppBundle\Entity\LocalizedEmailTemplate;
use AppBundle\Form\EmailTemplateType;
use AppBundle\Form\LocalizedEmailTemplateType;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * @View
 * @RouteResource("Translation")
 * @ParamConverter("localizedEmailTemplate", options={"mapping": {"emailTemplate": "template", "localizedEmailTemplate": "locale"}})
 */
class LocalizedEmailTemplateController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request, EmailTemplate $emailTemplate)
    {
        $this->denyAccessUnlessGranted('VIEW', $emailTemplate);
        return $this->paginate($emailTemplate->getLocalizedTemplates(), $request);
    }

    public function getAction(EmailTemplate $emailTemplate, LocalizedEmailTemplate $localizedEmailTemplate)
    {
        $this->denyAccessUnlessGranted('VIEW', $emailTemplate);
        return $localizedEmailTemplate;
    }

    public function newAction(EmailTemplate $emailTemplate)
    {
        $this->denyAccessUnlessGranted('EDIT', $emailTemplate);
        $localizedEmailTemplate = new LocalizedEmailTemplate($emailTemplate);

        return $this->createForm(LocalizedEmailTemplateType::class, $localizedEmailTemplate, [
            'method' => 'POST',
            'action' => $this->generateUrl('post_emailtemplate_translation', ['emailTemplate' => $emailTemplate->getId()]),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @View("AppBundle:LocalizedEmailTemplate:new.html.twig")
     */
    public function postAction(Request $request, EmailTemplate $emailTemplate)
    {
        $form = $this->newAction($emailTemplate);

        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();
            return $this->redirectToRoute('get_emailtemplate_translation', ['emailTemplate' => $emailTemplate->getId(), 'localizedEmailTemplate' => $form->getData()->getLocale()]);
        }
        return $form;
    }

    public function editAction(EmailTemplate $emailTemplate, LocalizedEmailTemplate $localizedEmailTemplate)
    {
        $this->denyAccessUnlessGranted('EDIT', $emailTemplate);
        return $this->createForm(LocalizedEmailTemplateType::class, $localizedEmailTemplate, [
            'method' => 'PUT',
            'action' => $this->generateUrl('put_emailtemplate_translation', ['emailTemplate' => $emailTemplate->getId(), 'localizedEmailTemplate' => $localizedEmailTemplate->getLocale()]),
        ])->add('submit', SubmitType::class, ['label'=>'form.submit']);
    }

    /**
     * @View("AppBundle:LocalizedEmailTemplate:edit.html.twig")
     */
    public function putAction(Request $request, EmailTemplate $emailTemplate, LocalizedEmailTemplate $localizedEmailTemplate)
    {
        $form = $this->editAction($emailTemplate, $localizedEmailTemplate);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtemplate_translation', ['emailTemplate' => $emailTemplate->getId(), 'localizedEmailTemplate' => $localizedEmailTemplate->getLocale()]);
        }
        return $form;
    }

    public function removeAction(EmailTemplate $emailTemplate, LocalizedEmailTemplate $localizedEmailTemplate)
    {
        $this->denyAccessUnlessGranted('DELETE', $emailTemplate);
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.localizedEmailTemplate.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('delete_emailtemplate_translation', ['emailTemplate' => $emailTemplate->getId(), 'localizedEmailTemplate' => $localizedEmailTemplate->getLocale()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:LocalizedEmailTemplate:remove.html.twig")
     */
    public function deleteAction(Request $request, EmailTemplate $emailTemplate, LocalizedEmailTemplate $localizedEmailTemplate)
    {
        $form = $this->removeAction($emailTemplate, $localizedEmailTemplate);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->remove($localizedEmailTemplate);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('get_emailtemplate_translations', ['emailTemplate' => $emailTemplate->getId()]);
        }
        return $form;
    }
}
