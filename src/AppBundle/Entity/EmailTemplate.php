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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
class EmailTemplate implements AutoAclInterface
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
     * @var EmailAddress|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\EmailAddress", cascade={"remove", "persist"})
     * @Assert\Valid()
     */
    private $sender;

    /**
     * @var LocalizedEmailTemplate[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\LocalizedEmailTemplate", mappedBy="template", cascade={"remove", "persist"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\NotNull()
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

    public function getNameIfNotInline()
    {
        if(strpos($this->name, '__inline__') !== 0)
            return $this->name;
        return null;
    }

    /**
     * @return EmailAddress|null
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param EmailAddress|null $sender
     * @return EmailTemplate
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return Collection<LocalizedEmailTemplate>
     */
    public function getLocalizedTemplates()
    {
        return $this->localizedTemplates;
    }

    public function getLocalizedTemplate($locale, $fallbackLocale = 'en')
    {
        $template = $this->localizedTemplates->matching(
            Criteria::create()
                ->where(Criteria::expr()->eq('locale', $locale))
                ->orWhere(Criteria::expr()->eq('locale', $fallbackLocale))
                // Order so $locale always ends up before $fallbackLocale
                // If $locale is lexicographically after $fallbackLocale, sort DESC so it appears first
                ->orderBy(['locale' => $locale>$fallbackLocale?'DESC':'ASC'])
                ->setMaxResults(1)
        )->first();

        if($template)
            return $template;
        // Could not find preferred or fallback locale, just take the first template
        return $this->localizedTemplates->first();
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

    public function getAclConfig()
    {
        $config = [
            self::CURRENT_USER => MaskBuilder::MASK_MASTER,
            'ROLE_ADMIN' => MaskBuilder::MASK_OWNER,
        ];
        return $config;
    }
}
