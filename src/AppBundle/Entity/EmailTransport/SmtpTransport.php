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

use AppBundle\Form\EmailTransport\SmtpTransportType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SmtpTransport
 *
 * @ORM\Entity()
 */
class SmtpTransport extends EmailTransport
{
    const FORM_TYPE=SmtpTransportType::class;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_host", type="string")
     * @Assert\NotBlank()
     */
    private $host;

    /**
     * @var integer
     *
     * @ORM\Column(name="mail_port", type="integer")
     * @Assert\Type("integer")
     */
    private $port;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_security", type="string", nullable=true)
     * @Assert\Choice({"ssl", "tls", null})
     */
    private $security;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_username", type="string")
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_password", type="string")
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     * @return SmtpTransport
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return SmtpTransport
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * @param string $security
     * @return SmtpTransport
     */
    public function setSecurity($security)
    {
        $this->security = $security;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return SmtpTransport
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return SmtpTransport
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return \Swift_Transport
     */
    public function getSwiftTransport()
    {
        $transport = \Swift_SmtpTransport::newInstance($this->host, $this->port, $this->security);
        $transport->setUsername($this->username);
        $transport->setPassword($this->password);
        return $transport;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'smtp';
    }
}

