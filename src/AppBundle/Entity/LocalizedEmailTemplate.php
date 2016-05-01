<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LocalizedEmailTemplate
 *
 * @ORM\Table(name="localized_email_template")
 * @ORM\Entity()
 * @UniqueEntity(fields={"template", "locale"}, errorPath="locale")
 */
class LocalizedEmailTemplate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\EmailTemplate", inversedBy="localizedTemplates")
     * @Assert\Valid()
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=2)
     *
     * @Assert\Language()
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="text")
     * @Assert\NotBlank()
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     * @Assert\NotBlank()
     */
    private $body;

    /**
     * LocalizedEmailTemplate constructor.
     * @param EmailTemplate $template
     */
    public function __construct(EmailTemplate $template = null)
    {
        $this->template = $template;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param EmailTemplate $template
     * @return LocalizedEmailTemplate
     */
    public function setTemplate(EmailTemplate $template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $locale
     * @return LocalizedEmailTemplate
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return LocalizedEmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return LocalizedEmailTemplate
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}

