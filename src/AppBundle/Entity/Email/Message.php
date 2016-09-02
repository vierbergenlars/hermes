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

namespace AppBundle\Entity\Email;

use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\EmailTransport\EmailTransport;
use AppBundle\Security\Acl\AutoAclInterface;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message
 *
 * @ORM\Table(name="email_message")
 * @ORM\Entity(repositoryClass="MessageRepository")
 */
class Message implements AutoAclInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var EmailAddress
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\EmailAddress")
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $sender;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\EmailTemplate", cascade={"persist"})
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $template;

    /**
     * @var array
     *
     * @ORM\Column(name="template_data", type="array")
     * @Assert\Type("array")
     */
    private $templateData;

    /**
     * @var Recipient[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Email\Recipient", mappedBy="message")
     * @Assert\Valid()
     */
    private $recipients;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="scheduled_send_time", type="datetime", nullable=true)
     */
    private $scheduledSendTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="queued_time", type="datetime", nullable=true)
     */
    private $queuedTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_time", type="datetime", nullable=true)
     */
    private $sentTime;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer")
     * @Assert\GreaterThan(0)
     */
    private $priority;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
        $this->templateData = [];
        $this->priority = 1;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getScheduledSendTime()
    {
        if(!$this->scheduledSendTime)
            return null;
        return \DateTimeImmutable::createFromMutable($this->scheduledSendTime);
    }

    /**
     * @param \DateTime $scheduledSendTime
     * @return Message
     */
    public function setScheduledSendTime($scheduledSendTime)
    {
        $this->scheduledSendTime = $scheduledSendTime;
        return $this;
    }

    /**
     * @return bool
     */
    public function isScheduled()
    {
        return $this->scheduledSendTime !== null;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getSentTime()
    {
        if(!$this->sentTime)
            return null;
        return \DateTimeImmutable::createFromMutable($this->sentTime);
    }

    /**
     * @param \DateTime $sentTime
     * @return Message
     */
    public function setSentTime($sentTime)
    {
        $this->sentTime = $sentTime;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSent()
    {
        return $this->sentTime !== null;
    }

    /**
     * Set templateData
     *
     * @param array $templateData
     *
     * @return Message
     */
    public function setTemplateData($templateData)
    {
        $this->templateData = $templateData;

        return $this;
    }

    /**
     * Get templateData
     *
     * @return array
     */
    public function getTemplateData()
    {
        return (array)$this->templateData;
    }

    /**
     * Set sender
     *
     * @param \AppBundle\Entity\EmailAddress $sender
     *
     * @return Message
     */
    public function setSender(\AppBundle\Entity\EmailAddress $sender = null)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return \AppBundle\Entity\EmailAddress
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set template
     *
     * @param \AppBundle\Entity\EmailTemplate $template
     *
     * @return Message
     */
    public function setTemplate(\AppBundle\Entity\EmailTemplate $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return \AppBundle\Entity\EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Add recipient
     *
     * @param \AppBundle\Entity\Email\Recipient $recipient
     *
     * @return Message
     */
    public function addRecipient(\AppBundle\Entity\Email\Recipient $recipient)
    {
        $this->recipients[] = $recipient;

        return $this;
    }

    /**
     * Remove recipient
     *
     * @param \AppBundle\Entity\Email\Recipient $recipient
     */
    public function removeRecipient(\AppBundle\Entity\Email\Recipient $recipient)
    {
        $this->recipients->removeElement($recipient);
    }

    /**
     * Get original recipients
     *
     * @return Recipient[]|Collection
     */
    public function getOriginalRecipients()
    {
        return $this->recipients->matching(
            Criteria::create()
                ->where(Criteria::expr()->isNull('originatingRecipient'))
        );
    }

    /**
     * Get recipients
     * @return Recipient[]|Collection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }


    public function getAclConfig()
    {
        return [
            self::CURRENT_USER => MaskBuilder::MASK_MASTER,
            'ROLE_ADMIN' => MaskBuilder::MASK_OWNER,
        ];
    }

    /**
     * @return \DateTime|null
     */
    public function getQueuedTime()
    {
        return $this->queuedTime;
    }

    /**
     * @param \DateTime $queuedTime
     *
     * @return Message
     */
    public function setQueuedTime($queuedTime)
    {
        $this->queuedTime = $queuedTime;

        return $this;
    }

    /**
     * @return bool
     */
    public function isQueued()
    {
        return $this->queuedTime !== null;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return Message
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }
}
