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
use AppBundle\Entity\Email\QueuedMessage;
use AppBundle\Entity\Email\QueuedMessageRepository;
use AppBundle\Entity\Email\Recipient;
use AppBundle\Event\QueueMessageEvent;
use AppBundle\Event\SendMessageEvent;
use AppBundle\Event\UpdateRecipientsEvent;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class DeliverMessagesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:deliver-messages')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of mails to send during this run.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em EntityManager */
        $repo = $em->getRepository(QueuedMessage::class);
        /* @var $repo QueuedMessageRepository */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        /* @var $eventDispatcher EventDispatcher */
        $mailer = $this->getContainer()->get('swiftmailer.mailer');
        /* @var $mailer \Swift_Mailer */
        $messages = $repo->findSendableMessages($input->getOption('limit'));

        foreach($messages as $message) {
            /* @var $message QueuedMessage */
            try {
                $sendMessage = new SendMessageEvent($message);
                $eventDispatcher->dispatch(SendMessageEvent::EVENT_NAME, $sendMessage);
                if($mailer->send($sendMessage->getSwiftMessage()) !== 1)
                    throw new \RuntimeException('Mailtransport returned failure.');

                $message->setSentAt(new \DateTime());
                $message->getSourceRecipient()->setSentTime(new \DateTime());
                $output->writeln(sprintf('Message queue id %s sent.', $message->getId()));
            } catch(\Exception $ex) {
                $message->setFailedAt(new \DateTime());
                $message->getSourceRecipient()->setFailedTime(new \DateTime());
                $message->getSourceRecipient()->setFailureMessage($ex->getMessage());
                $output->writeln(sprintf('<error>Message queue id %d could not be sent: %s</error>', $message->getId(), $ex->__toString()), OutputInterface::VERBOSITY_QUIET);
            }
            $eventDispatcher->dispatch(UpdateRecipientsEvent::EVENT_NAME, new UpdateRecipientsEvent($message->getSourceRecipient()));
            $em->flush();
        }
    }
}
