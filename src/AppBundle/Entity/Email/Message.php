<?php

namespace AppBundle\Entity\Email;

use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\EmailTransport\EmailTransport;
use AppBundle\Security\Acl\AutoAclInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message
 *
 * @ORM\Table(name="email_message")
 * @ORM\Entity
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
     * @var EmailTransport
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\EmailTransport\EmailTransport")
     * @Assert\Valid()
     */
    private $transport;

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
     * @ORM\Column(name="sent_time", type="datetime", nullable=true)
     */
    private $sentTime;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
        $this->templateData = [];
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
        return $this->templateData;
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
     * Set transport
     *
     * @param \AppBundle\Entity\EmailTransport\EmailTransport $transport
     *
     * @return Message
     */
    public function setTransport(\AppBundle\Entity\EmailTransport\EmailTransport $transport = null)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Get transport
     *
     * @return \AppBundle\Entity\EmailTransport\EmailTransport
     */
    public function getTransport()
    {
        return $this->transport;
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
     * Get recipients
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    public function getAclConfig()
    {
        return self::DEFAULT_ACL_CONFIG;
    }
}
