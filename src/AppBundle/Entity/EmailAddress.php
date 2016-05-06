<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EmailAddress
 *
 * @ORM\Entity
 * @UniqueEntity(fields={"email"})
 */
class EmailAddress
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
}
