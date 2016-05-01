<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EmailTemplate
 *
 * @ORM\Table(name="email_template")
 * @ORM\Entity
 * @UniqueEntity(fields={"name"}, errorPath="name")
 */
class EmailTemplate
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var LocalizedEmailTemplate[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\LocalizedEmailTemplate", mappedBy="template", cascade={"remove", "persist"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\Count(min=1)
     */
    private $localizedTemplates;

    /**
     * EmailTemplate constructor.
     */
    public function __construct()
    {
        $this->localizedTemplates = new ArrayCollection();
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
     * @return EmailTemplate
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
     * @return Collection<LocalizedEmailTemplate>
     */
    public function getLocalizedTemplates()
    {
        return $this->localizedTemplates;
    }


    /**
     * Add localizedTemplate
     *
     * @param \AppBundle\Entity\LocalizedEmailTemplate $localizedTemplate
     *
     * @return EmailTemplate
     */
    public function addLocalizedTemplate(\AppBundle\Entity\LocalizedEmailTemplate $localizedTemplate)
    {
        $this->localizedTemplates[] = $localizedTemplate;
        $localizedTemplate->setTemplate($this);

        return $this;
    }

    /**
     * Remove localizedTemplate
     *
     * @param \AppBundle\Entity\LocalizedEmailTemplate $localizedTemplate
     */
    public function removeLocalizedTemplate(\AppBundle\Entity\LocalizedEmailTemplate $localizedTemplate)
    {
        $this->localizedTemplates->removeElement($localizedTemplate);
    }
}
