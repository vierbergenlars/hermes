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

use AppBundle\Entity\Email\Recipient;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class QueuedMessage
 *
 * @ORM\Entity(repositoryClass="QueuedMessageRepository")
 * @ORM\Table(name="email_queued_message")
 */
class QueuedMessage
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
     * @var Recipient
     *
     * @ORM\ManyToOne(targetEntity="Recipient")
     */
    private $sourceRecipient;

    /**
     * @var string
     *
     * @ORM\Column(name="sender", type="string", length=255)
     */
    private $sender;

    /**
     * @var string
     *
     * @ORM\Column(name="from_address", type="string", length=255)
     */
    private $fromAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="from_name", type="string", length=255, nullable=true)
     */
    private $fromName;

    /**
     * @var string
     *
     * @ORM\Column(name="to_name", type="string", length=255, nullable=true)
     */
    private $toName;

    /**
     * @var string
     *
     * @ORM\Column(name="to_address", type="string", length=255)
     */
    private $toAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="text")
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     */
    private $body;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    private $sentAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="failed_at", type="datetime", nullable=true)
     */
    private $failedAt;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function isSent()
    {
        return $this->sentAt !== null;
    }

    /**
     * Get sentAt
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     *
     * @return QueuedMessage
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isFailed()
    {
        return $this->failedAt !== null;
    }

    /**
     * Get failedAt
     *
     * @return \DateTime
     */
    public function getFailedAt()
    {
        return $this->failedAt;
    }

    /**
     * Set failedAt
     *
     * @param \DateTime $failedAt
     *
     * @return QueuedMessage
     */
    public function setFailedAt($failedAt)
    {
        $this->failedAt = $failedAt;

        return $this;
    }

    /**
     * Get message
     *
     * @return \AppBundle\Entity\Email\Recipient
     */
    public function getSourceRecipient()
    {
        return $this->sourceRecipient;
    }

    /**
     * Set message
     *
     * @param \AppBundle\Entity\Email\Recipient $sourceRecipient
     * @return QueuedMessage
     */
    public function setSourceRecipient(Recipient $sourceRecipient = null)
    {
        $this->sourceRecipient = $sourceRecipient;

        return $this;
    }

    /**
     * Get emailaddress
     *
     * @return string
     */
    public function getToAddress()
    {
        return $this->toAddress;
    }

    /**
     * Set emailaddress
     *
     * @param string $toAddress
     *
     * @return QueuedMessage
     */
    public function setToAddress($toAddress)
    {
        $this->toAddress = $toAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     * @return QueuedMessage
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * @param string $fromAddress
     * @return QueuedMessage
     */
    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }
    /**
     * @param string $fromName
     * @return QueuedMessage
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
        return $this;
    }

    /**
     * @return string
     */
    public function getToName()
    {
        return $this->toName;
    }

    /**
     * @param string $toName
     * @return QueuedMessage
     */
    public function setToName($toName)
    {
        $this->toName = $toName;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return QueuedMessage
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return QueuedMessage
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return \Swift_Message
     */
    public function getMimeMessage()
    {
        return \Swift_Message::newInstance()
            ->setSender($this->getSender())
            ->setFrom($this->getFromAddress(), $this->getFromName())
            ->setTo($this->getToAddress(), $this->getToName())
            ->setSubject($this->getSubject())
            ->setBody($this->getBody());
    }

}
