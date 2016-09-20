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

namespace AppBundle\Entity;

use AppBundle\Security\Acl\AutoAclInterface;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EmailAddress
 *
 * @ORM\Entity
 * @UniqueEntity(fields={"email"})
 */
class EmailAddress implements AutoAclInterface
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\Length(max=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @Assert\Length(max=255)
     * @Assert\Email()
     * @Assert\NotBlank()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="authCode", type="string", length=64, nullable=true)
     */
    private $authCode;

    public function __construct()
    {
        $this->regenerateAuthCode();
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
     * Set name
     *
     * @param string $name
     *
     * @return EmailAddress
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return EmailAddress
     */
    public function setEmail($email)
    {
        if($this->email !== $email) {
            $this->regenerateAuthCode();
            $this->email = $email;
        }

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get verified
     *
     * @return bool
     */
    public function isVerified()
    {
        return $this->authCode === null;
    }

    private function regenerateAuthCode()
    {
        $this->authCode = bin2hex(random_bytes(32));
    }

    /**
     * Get authCode
     *
     * @return string
     */
    public function getAuthCode()
    {
        return $this->authCode;
    }

    public function confirmAuthCode($authCode)
    {
        if($authCode === $this->authCode) {
            $this->authCode = null;
            return true;
        }
        return false;
    }

    public function getAclConfig()
    {
        return [
            self::CURRENT_USER => MaskBuilder::MASK_MASTER,
            'ROLE_ADMIN' => MaskBuilder::MASK_OWNER,
        ];
    }

    public function __toString()
    {
        return sprintf('%s <%s>', $this->name, $this->email);
    }
}

