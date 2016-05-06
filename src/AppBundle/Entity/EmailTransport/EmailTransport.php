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

namespace AppBundle\Entity\EmailTransport;

use AppBundle\Form\EmailTransport\EmailTransportType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


/**
 * EmailTransport
 *
 * @ORM\Table(name="email_transport")
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="transport_type", type="string")
 * @ORM\DiscriminatorMap({"mail"="MailTransport", "smtp"="SmtpTransport"})
 * @UniqueEntity(fields={"name"})
 */
abstract class EmailTransport
{
    const FORM_TYPE = EmailTransportType::class;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="delivery_starttime", type="datetime")
     * @Assert\DateTime()
     */
    private $deliveryStarttime;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_period_duration", type="string")
     * @Assert\NotBlank()
     * @Assert\Callback({"AppBundle\Entity\EmailTransport\EmailTransport","validateDuration"})
     */
    private $deliveryPeriodDuration;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_period_maxmails", type="integer")
     *
     * @Assert\NotBlank()
     * @Assert\GreaterThanOrEqual(1)
     */
    private $deliveryPeriodMaxmails;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_latency", type="string")
     * @Assert\NotBlank()
     * @Assert\Callback({"AppBundle\Entity\EmailTransport\EmailTransport","validateDuration"})
     */
    private $deliveryLatency;

    /**
     * EmailTransport constructor.
     */
    public function __construct()
    {
        $this->deliveryStarttime = new \DateTime();
        $this->deliveryPeriodDuration = 'PT0S';
        $this->deliveryLatency = 'PT0S';
    }

    /**$a
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return EmailTransport
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set deliveryStarttime
     *
     * @param \DateTimeInterface $deliveryStarttime
     *
     * @return EmailTransport
     */
    public function setDeliveryStarttime(\DateTimeInterface $deliveryStarttime)
    {
        $this->deliveryStarttime = $deliveryStarttime;

        return $this;
    }

    /**
     * Get deliveryStarttime
     *
     * @return \DateTimeImmutable
     */
    public function getDeliveryStarttime()
    {
        if($this->deliveryStarttime instanceof \DateTimeImmutable)
            return $this->deliveryStarttime;
        return \DateTimeImmutable::createFromMutable($this->deliveryStarttime);
    }

    /**
     * @return \DateInterval
     */
    public function getDeliveryPeriodDuration()
    {
        return new \DateInterval($this->deliveryPeriodDuration);
    }

    /**
     * @param \DateInterval|string $deliveryPeriodDuration
     * @return EmailTransport
     */
    public function setDeliveryPeriodDuration($deliveryPeriodDuration)
    {
        $this->deliveryPeriodDuration = $deliveryPeriodDuration instanceof \DateInterval?$deliveryPeriodDuration->format("P%yY%mM%dDT%hH%iM%sS"):$deliveryPeriodDuration;
        return $this;
    }

    /**
     * Set deliveryPeriodMaxmails
     *
     * @param integer $deliveryPeriodMaxmails
     *
     * @return EmailTransport
     */
    public function setDeliveryPeriodMaxmails($deliveryPeriodMaxmails)
    {
        $this->deliveryPeriodMaxmails = $deliveryPeriodMaxmails;

        return $this;
    }

    /**
     * Get deliveryPeriodMaxmails
     *
     * @return int
     */
    public function getDeliveryPeriodMaxmails()
    {
        return $this->deliveryPeriodMaxmails;
    }

    /**
     * @return \DateInterval
     */
    public function getDeliveryLatency()
    {
        return new \DateInterval($this->deliveryLatency);
    }

    /**
     * @param \DateInterval|string $deliveryLatency
     * @return EmailTransport
     */
    public function setDeliveryLatency($deliveryLatency)
    {
        $this->deliveryLatency = $deliveryLatency instanceof \DateInterval?$deliveryLatency->format("P%yY%mM%dDT%hH%iM%sS"):$deliveryLatency;
        return $this;
    }

    /**
     * @return \Swift_Transport
     */
    abstract public function getSwiftTransport();

    /**
     * @return string
     */
    abstract public function getType();

    public static function validateDuration($duration, ExecutionContextInterface $executionContext)
    {
        try {
            new \DateInterval($duration);
        } catch(\Exception $ex) {
            $executionContext->addViolation($ex->getMessage());
        }

    }
}

