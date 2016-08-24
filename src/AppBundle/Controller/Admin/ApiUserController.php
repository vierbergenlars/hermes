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
use AppBundle\Entity\ApiUser;
use AppBundle\Form\ApiUserType;
use FOS\RestBundle\Controller\Annotations\NoRoute;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * @View
 * @RouteResource("Apiuser")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ApiUserController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:ApiUser')
            ->createQueryBuilder('u')
        ;
        return $this->paginate($queryBuilder, $request);
    }

    public function getAction(ApiUser $user)
    {
        return $user;
    }

    /**
     * @return mixed
     */
    public function newAction()
    {
        return $this->createForm(ApiUserType::class, new ApiUser(), [
            'method' => 'POST',
            'action' => $this->generateUrl('admin_post_apiuser'),
        ]);
    }

    /**
     * @View("AppBundle:Admin/ApiUser:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->newAction();

        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->persist($form->getData());
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('admin_get_apiuser', ['user' => $form->getData()->getId()]);
        }
        return $form;
    }

    public function editAction(ApiUser $user)
    {
        return $this->createForm(ApiUserType::class, $user, [
            'method' => 'PUT',
            'action' => $this->generateUrl('admin_put_apiuser', ['user' => $user->getId()]),
        ]);
    }

    /**
     * @View("AppBundle:Admin/ApiUser:edit.html.twig")
     */
    public function putAction(Request $request, ApiUser $user)
    {
        $form = $this->editAction($user);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('admin_get_apiuser', ['user' => $user->getId()]);
        }
        return $form;
    }

    /**
     * @NoRoute
     * @View
     */
    public function rotateFormAction(ApiUser $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_rotate_apiuser', ['user' => $user->getId()]))
            ->add('rotate', SubmitType::class, [
                'label' => 'label.rotate_key',
                'button_class' => 'danger btn-xs'
            ])
            ->getForm();
    }

    /**
     * @Post
     */
    public function rotateAction(Request $request, ApiUser $user)
    {
        $form = $this->rotateFormAction($user);
        $form->handleRequest($request);
        if(!$form->isValid()) {
            $this->addFlash('danger', 'API key secret could not be regenerated.');
        } else {
            $user->updatePassword();
            $this->getEntityManager()->flush();
        }

        return $this->redirectToRoute('admin_get_apiuser', ['user' => $user->getId()]);
    }

    public function removeAction(ApiUser $user)
    {
        return $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'admin.user.delete',
                'button_class' => 'danger'
            ])
            ->setMethod('DELETE')
            ->setAction($this->generateUrl('admin_delete_apiuser', ['user' => $user->getId()]))
            ->getForm();
    }

    /**
     * @View("AppBundle:Admin/ApiUser:remove.html.twig")
     */
    public function deleteAction(Request $request, ApiUser $user)
    {
        $form = $this->removeAction($user);
        $form->handleRequest($request);
        if($form->isValid()) {
            $this->getEntityManager()->remove($user);
            $this->getEntityManager()->flush();

            return $this->redirectToRoute('admin_get_apiusers');
        }
        return $form;
    }
}
