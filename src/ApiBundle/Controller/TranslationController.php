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

namespace ApiBundle\Controller;

use AppBundle\Entity\Email\Message;
use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\LocalizedEmailTemplate;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\DeserializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @ParamConverter("locale", options={"mapping": {"template": "template", "locale": "locale"}})
 * @View(serializerGroups={"Default", "translation"})
 * @Security("is_granted('VIEW', template)")
 */
class TranslationController extends BaseController implements ClassResourceInterface
{
    public function getAction(EmailTemplate $template, LocalizedEmailTemplate $locale)
    {
        return $locale;
    }

    /**
     * @Security("is_granted('EDIT', template)")
     */
    public function postAction(Request $request, EmailTemplate $template)
    {
        $deserializationContext = DeserializationContext::create()
            ->setGroups(['Default', 'translation']);
        $em = $this->getEntityManager();
        $localizedTemplate = $this->deserializeRequest($request, LocalizedEmailTemplate::class, $deserializationContext,
            function(LocalizedEmailTemplate $localizedEmailTemplate) use($template) {
                $localizedEmailTemplate->setTemplate($template);
            });
        $em->persist($localizedTemplate);
        $em->flush();
        return $this->routeRedirectView('api_get_template_translation', [
            'template' => $template->getId(),
            'locale' => $localizedTemplate->getLocale(),
        ]);
    }

    /**
     * @Security("is_granted('EDIT', template)")
     */
    public function putAction(Request $request, EmailTemplate $template, LocalizedEmailTemplate $locale)
    {
        $deserializationContext = DeserializationContext::create()
            ->setGroups(['Default', 'translation']);
        $localizedTemplate = $this->deserializeRequest($request, LocalizedEmailTemplate::class, $deserializationContext,
            function(LocalizedEmailTemplate $localizedEmailTemplate, ConstraintViolationListInterface $errors) use($template, $locale) {
                if(!$localizedEmailTemplate->getLocale())
                    $localizedEmailTemplate->setLocale($locale->getLocale());
                if($localizedEmailTemplate->getLocale() !== $locale->getLocale())
                    $errors->add(new ConstraintViolation(
                        'Locale of a translation cannot be changed.',
                        'Locale of a translation cannot be changed.',
                        [],
                        $localizedEmailTemplate,
                        'locale',
                        $localizedEmailTemplate->getLocale()
                    ));
            });
        $locale->setSubject($localizedTemplate->getSubject());
        $locale->setBody($localizedTemplate->getBody());
        $this->getEntityManager()->flush();
    }

    /**
     * @Security("is_granted('EDIT', template)")
     */
    public function deleteAction(EmailTemplate $template, LocalizedEmailTemplate $locale)
    {
        $this->getEntityManager()->remove($locale);
        $this->getEntityManager()->flush();
    }
}
