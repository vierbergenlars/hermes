<?php
use Symfony\Component\Security\Acl\Model\EntryInterface;

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

namespace vierbergenlars\Bundle\AclBundle\VlAclBundle\Controller;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use vierbergenlars\Bundle\AclBundle\VlAclBundle\DataProvider\DataProviderInterface;

/**
 * @NamePrefix("vl_acl_")
 */
class AclController extends Controller implements ClassResourceInterface
{
    /**
     * @return DataProviderInterface
     */
    private function getDataProvider()
    {
        return $this->get('vl_acl.data_provider');

    }

    /**
     * @return \Symfony\Component\Security\Acl\Dbal\MutableAclProvider
     */
    private function getAclProvider()
    {
        return $this->get('security.acl.provider');
    }

    private function getObjectIdentity(Request $request)
    {
        if ($request->attributes->has('object')) {
            $oid = ObjectIdentity::fromDomainObject($request->attributes->get('object'));
        } else {
            $objectClass = $request->get('object_class');
            $objectId = $request->get('object_id');
            $oid = new ObjectIdentity($objectId, $objectClass);
        }
        return $oid;
    }

    /**
     * @View()
     */
    public function getAction(Request $request)
    {
        $oid = $this->getObjectIdentity($request);
        $this->denyAccessUnlessGranted('VIEW', $oid);
        $acl = $this->getAclProvider()->findAcl($oid);
        return [
            'masks' => $this->getDataProvider()->getMasksMap(),
            'data' => $acl,
            'request' => $request->attributes->get('req', $request),
        ];
    }

    /**
     * @View()
     */
    public function newAceAction(Request $request)
    {
        $oid = $this->getObjectIdentity($request);
        $this->denyAccessUnlessGranted('MASTER', $oid);
        $acl = $this->getAclProvider()->findAcl($oid);
        if(!$acl)
            throw $this->createNotFoundException('ACL not found');

        $form = $this->createFormBuilder()
            ->add('role', ChoiceType::class, [
                'required' => false,
                'choices_as_values' => true,
                'choices' => $this->getDataProvider()->getRoles(),
            ])
            ->add('user', EntityType::class, [
                'required' => false,
                'class' => $this->getDataProvider()->getUserClass(),
                'choice_label' => 'username',
            ])
            ->setAction($this->generateUrl('vl_acl_post_acl_ace', $request->query->all()))
            ->add('submit', SubmitType::class)
            ->getForm();

        return [
            'acl' => $acl,
            'form' => $form,
        ];
    }

    /**
     * @View(template="VlAclBundle:Acl:newAce.html.twig")
     */
    public function postAceAction(Request $request)
    {
        $data = $this->newAceAction($request);
        $form = $data['form'];
        /* @var $form Form */
        $acl = $data['acl'];
        /* @var $acl MutableAclInterface */

        $form->handleRequest($request);
        if(!($form->get('role')->getData() || $form->get('user')->getData()))
            $form->addError(new FormError('Fill in at least one of role and user'));
        if($form->isValid()) {
            $sid = null;
            if($form->get('role')->getData()) {
                $sid = new RoleSecurityIdentity($form->get('role')->getData());
                $acl->insertObjectAce($sid, MaskBuilder::MASK_VIEW, count($acl->getObjectAces()));
            }
            if($form->get('user')->getData()) {
                $sid = UserSecurityIdentity::fromAccount($form->get('user')->getData());
                $acl->insertObjectAce($sid, MaskBuilder::MASK_VIEW, count($acl->getObjectAces()));
            }

            $this->getAclProvider()->updateAcl($acl);
            return $this->redirectToRoute($request->get('target_route'), $request->get('target_route_params'));
        }
        return $form;
    }

    /**
     * @View()
     */
    public function editAceAction(Request $request)
    {
        $oid = $this->getObjectIdentity($request);
        $this->denyAccessUnlessGranted('MASTER', $oid);
        $acl = $this->getAclProvider()->findAcl($oid);
        switch ($request->get('ace_type')) {
            case 'object':
                $aces = $acl->getObjectAces();
                break;
            case 'class':
                $aces = $acl->getClassAces();
                break;
            default:
                throw $this->createNotFoundException('ace_type must be object or class');
        }
        $foundAce = null;
        foreach ($aces as $index => $ace) {
            /* @var $ace EntryInterface */
            if ($ace->getId() == $request->get('ace_id')) {
                $foundAce = $ace;
                break;
            }
        }
        if (!$foundAce)
            throw $this->createNotFoundException('ACE not found');

        $formBuilder = $this->createFormBuilder();
        foreach ($this->getDataProvider()->getMasksMap() as $name => $value) {
            $formBuilder->add(strtolower($name), CheckboxType::class, [
                'required' => false,
                'data' => ($foundAce->getMask() & $value) !== 0,
            ]);
        }

        if(!$this->isGranted('OWNER', $oid))
            $formBuilder->get('owner')->setDisabled(true);

        $formBuilder->add('submit', SubmitType::class);
        $formBuilder
            ->setMethod('PUT')
            ->setAction($this->generateUrl('vl_acl_put_acl_ace', $request->query->all()));

        return [
            'ace' => $foundAce,
            'ace_index' => $index,
            'ace_type' => $request->get('ace_type'),
            'form' => $formBuilder->getForm(),
        ];
    }

    /**
     * @View(template="VlAclBundle:Acl:editAce.html.twig")
     */
    public function putAceAction(Request $request)
    {
        $data = $this->editAceAction($request);
        $form = $data['form'];
        /* @var $form Form */
        $ace = $data['ace'];
        /* @var $ace EntryInterface */
        $aceIndex  = $data['ace_index'];


        $form->handleRequest($request);
        if($form->isValid()) {
            $mask = 0;
            foreach($this->getDataProvider()->getMasksMap() as $name => $value) {
                if($form->get(strtolower($name))->getData()) {
                    $mask |= $value;
                } else {
                    $mask &= ~$value;
                }
            }
            $acl = $ace->getAcl();
            /* @var $acl MutableAclInterface */
            switch($data['ace_type']) {
                case 'object':
                    $acl->updateObjectAce($aceIndex, $mask);
                    if($mask === 0)
                        $acl->deleteObjectAce($aceIndex);
                    break;
                case 'class':
                    $acl->updateClassAce($aceIndex, $mask);
                    if($mask === 0)
                        $acl->deleteClassAce($aceIndex);
                    break;
                default:
                    throw new BadRequestHttpException('No such ACE type');
            }
            $this->getAclProvider()->updateAcl($acl);
            return $this->redirectToRoute($request->get('target_route'), $request->get('target_route_params'));
        }
        return $form;
    }

}
