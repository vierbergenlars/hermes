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

namespace AppBundle\EventListener;

use AppBundle\Security\Acl\AutoAclInterface;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class UpdateAclListener implements EventSubscriber
{
    /**
     * @var MutableAclProviderInterface
     */
    private $aclProvider;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    private $container;

    /**
     * UpdateAclListener constructor.
     * @param ContainerInterface $container
     * @param TokenStorage $tokenStorage
     */
    public function __construct(ContainerInterface $container, TokenStorage $tokenStorage)
    {
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postRemove,
        ];
    }

    private function getAclProvider()
    {
        if(!$this->aclProvider)
            $this->aclProvider = $this->container->get('security.acl.provider');
        return $this->aclProvider;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if($entity instanceof AutoAclInterface) {
            $acl = $this->getAclProvider()->createAcl(ObjectIdentity::fromDomainObject($args->getEntity()));
            foreach($entity->getAclConfig() as $role => $mask) {
                if($role === AutoAclInterface::CURRENT_USER) {
                    $sid = UserSecurityIdentity::fromToken($this->tokenStorage->getToken());
                } else {
                    $sid = new RoleSecurityIdentity($role);
                }
                $acl->insertObjectAce($sid, $mask);
            }

            $this->getAclProvider()->updateAcl($acl);
        }

    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if($entity instanceof AutoAclInterface) {
            $this->getAclProvider()->deleteAcl(ObjectIdentity::fromDomainObject($entity));
        }
    }
}
