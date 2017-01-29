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
 * @View(serializerGroups={"Default", "template"})
 */
class TemplateController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $candidateTemplates = $this->getEntityManager()
            ->getRepository('AppBundle:EmailTemplate')
            ->createQueryBuilder('e')
            ->where('e.name NOT LIKE \'__inline__%\'')
            ->getQuery()
            ->getResult()
        ;

        $authorizedTemplates = array_filter($candidateTemplates, function(EmailTemplate $template) {
            return $this->isGranted('USE', $template);
        });

        return $this->paginate($authorizedTemplates, $request);
    }

    /**
     * @Security("is_granted('VIEW', template)")
     * @View(serializerGroups={"Default", "template", "template_object"})
     */
    public function getAction(EmailTemplate $template)
    {
        return $template;
    }

    public function postAction(Request $request)
    {
        $deserializationContext = DeserializationContext::create()
            ->setGroups(['Default', 'template', 'template_object', 'template_POST']);
        $em = $this->getEntityManager();
        $template = $this->deserializeRequest($request, EmailTemplate::class, $deserializationContext,
            function(EmailTemplate $template, ConstraintViolationListInterface $errors) use($em) {
                if($template->getSender()) {
                    $sender = $em->getRepository(EmailAddress::class)->findOneBy([
                        'email' => $template->getSender()->getEmail(),
                        'authCode' => null,
                    ]);
                    if(!$sender) {
                        $errors->add(new ConstraintViolation(
                            'Sender does not exist: '.$template->getSender()->getEmail(),
                            'Sender does not exist: {sender}',
                            ['sender' => $template->getSender()->getEmail()],
                            $template,
                            'sender',
                            null
                        ));
                        return;
                    }
                    if(!$this->isGranted('USE', $sender)) {
                        $errors->add(new ConstraintViolation(
                            'Permission denied to use sender: '.$sender->getEmail(),
                            'Permission denied to use sender: {sender}',
                            ['sender' => $sender->getEmail()],
                            $template,
                            'sender',
                            null
                        ));
                        return;
                    }
                    $template->setSender($sender);
                }
                if($template->getLocalizedTemplates())
                    foreach($template->getLocalizedTemplates() as $localizedTemplate)
                        $localizedTemplate->setTemplate($template);
            });
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
        return $this->routeRedirectView('api_get_template', ['template' => $template->getId()]);
    }

    /**
     * @Security("is_granted('DELETE', template)")
     */
    public function deleteAction(EmailTemplate $template)
    {
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }

    /**
     * @Security("is_granted('USE', template)")
     */
    public function postMessageAction(Request $request, EmailTemplate $template)
    {
        $deserializationContext = DeserializationContext::create()
            ->setGroups(['Default', 'template', 'translation', 'template_object']);
        $em = $this->getEntityManager();
        $message = $this->deserializeRequest($request, Message::class, $deserializationContext,
            function(Message $message, ConstraintViolationListInterface $errors) use ($em, $template) {
                if($message->getSender()) {
                    $sender = $em->getRepository(EmailAddress::class)->findOneBy([
                        'email' => $message->getSender()->getEmail(),
                        'authCode' => null,
                    ]);
                    if(!$sender) {
                        $errors->add(new ConstraintViolation(
                            'Sender does not exist: '.$message->getSender()->getEmail(),
                            'Sender does not exist: {sender}',
                            ['sender' => $message->getSender()->getEmail()],
                            $message,
                            'sender',
                            null
                        ));
                        return;
                    }
                    if(!$this->isGranted('USE', $sender)) {
                        $errors->add(new ConstraintViolation(
                            'Permission denied to use sender: '.$sender->getEmail(),
                            'Permission denied to use sender: {sender}',
                            ['sender' => $sender->getEmail()],
                            $message,
                            'sender',
                            null
                        ));
                        return;
                    }
                    $message->setSender($sender);
                }
                if(!$message->getPriority())
                    $message->setPriority(1);
                $message->setTemplate($template);
            });
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
        return $this->routeRedirectView('api_get_message', ['message' => $message->getId()], Response::HTTP_CREATED);
    }
}
