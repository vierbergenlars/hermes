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

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Recipient
 *
 * @ORM\Table(name="email_recipient")
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="recipient_type", type="string")
 * @ORM\DiscriminatorMap({"std"="StandardRecipient", "authserver"="AuthserverRecipient", "group"="GroupRecipient"})
 */
abstract class Recipient
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
     * @var Recipient[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Email\Recipient", mappedBy="originatingRecipient", cascade={"remove"})
     */
    private $childRecipients;

    /**
     * @var Recipient
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Email\Recipient", inversedBy="childRecipients", cascade={"ALL"})
     */
    private $originatingRecipient;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Email\Message", inversedBy="recipients")
     */
    private $message;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="queued_time", type="datetime", nullable=true)
     */
    private $queuedTime;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="sent_time", type="datetime", nullable=true)
     */
    private $sentTime;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="failed_time", type="datetime", nullable=true)
     */
    private $failedTime;

    /**
     * @var \Exception
     *
     * @ORM\Column(name="failure_message", type="text", nullable=true)
     */
    private $failureMessage;

    /**
     * Recipient constructor.
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
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
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     * @return Recipient
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getQueuedTime()
    {
        return $this->queuedTime;
    }

    /**
     * @return bool
     */
    public function isQueued()
    {
        return $this->queuedTime !== null;
    }

    /**
     * @param \DateTime|null $queuedTime
     * @return Recipient
     */
    public function setQueuedTime($queuedTime)
    {
        $this->queuedTime = $queuedTime;
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
     * @return \DateTime|null
     */
    public function getSentTime()
    {
        return $this->sentTime;
    }

    /**
     * @param \DateTime|null $sentTime
     * @return Recipient
     */
    public function setSentTime($sentTime)
    {
        $this->sentTime = $sentTime;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getFailedTime()
    {
        return $this->failedTime;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->failedTime !== null;
    }

    /**
     * @param \DateTime|null $failedTime
     * @return Recipient
     */
    public function setFailedTime($failedTime)
    {
        $this->failedTime = $failedTime;
        return $this;
    }

    /**
     * @return Recipient
     */
    public function getOriginatingRecipient()
    {
        return $this->originatingRecipient;
    }

    /**
     * @param Recipient $originatingRecipient
     * @return Recipient
     */
    public function setOriginatingRecipient(Recipient $originatingRecipient)
    {
        $this->originatingRecipient = $originatingRecipient;
        return $this;
    }

    abstract public function __toString();

    /**
     * @return string
     */
    public function getFailureMessage()
    {
        return $this->failureMessage;
    }

    /**
     * @param string $failureMessage
     * @return Recipient
     */
    public function setFailureMessage($failureMessage)
    {
        $this->failureMessage = $failureMessage;
        return $this;
    }

    /**
     * @return Recipient[]|Collection|Selectable
     */
    public function getChildRecipients()
    {
        return $this->childRecipients;
    }
}

