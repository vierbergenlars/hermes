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

namespace AppBundle\Command;

use AppBundle\Entity\Email\Message;
use AppBundle\Entity\Email\MessageRepository;
use AppBundle\Entity\Email\Recipient;
use AppBundle\Event\QueueMessageEvent;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class QueueMessagesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:queue-messages')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em EntityManager */
        $repo = $em->getRepository('AppBundle:Email\Message');
        /* @var $repo MessageRepository */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        /* @var $eventDispatcher EventDispatcher */
        $messages = $repo->findQueueableMessages();

        foreach($messages as $message) {
            /* @var $message Message */
            try {
                foreach ($message->getRecipients() as $recipient) {
                    /* @var $recipient Recipient */
                    $eventDispatcher->dispatch('hermes.queue_message', new QueueMessageEvent($message, $recipient, ['message' => $message]));
                }
                $message->setQueuedTime(new \DateTime());
                $this->removeEditAcls($message);
                $output->writeln(sprintf('Message %s queued.', $message->getId()));
            } catch(\Exception $ex) {
                $output->writeln(sprintf('<error>Message %d could not be queued: %s</error>', $message->getId(), $ex->__toString()), OutputInterface::VERBOSITY_QUIET);
            }
            $em->flush();
        }
    }

    private function doesAceGrantPermission(EntryInterface $ace, $permission) {
        $permissionMap = $this->getContainer()->get('security.acl.permission.map');
        $masks = $permissionMap->getMasks($permission, null);
        foreach($masks as $mask)
            if($ace->getMask()&$mask)
                return true;
        return false;
    }

    private function removeEditAcls($message)
    {
        $aclProvider = $this->getContainer()->get('security.acl.provider');
        /* @var $aclProvider MutableAclProviderInterface */
        $oid = ObjectIdentity::fromDomainObject($message);
        $acl = $aclProvider->findAcl($oid);
        /* @var $acl MutableAclInterface */
        $newAces = [];
        foreach ($acl->getObjectAces() as $ace) {
            /* @var $ace EntryInterface */
            $newMask = 0;
            foreach ([MaskBuilder::MASK_VIEW, MaskBuilder::MASK_USE] as $mask) {
                if ($this->doesAceGrantPermission($ace, MaskBuilder::getName($mask))) {
                    $newMask |= $mask;
                }
            }
            $newAces[] = [$ace->getSecurityIdentity(), $newMask];
            $acl->deleteObjectAce(0);
        }
        foreach ($newAces as $idx => $newAce) {
            $acl->insertObjectAce($newAce[0], $newAce[1], $idx);
        }
        $aclProvider->updateAcl($acl);
    }
}
