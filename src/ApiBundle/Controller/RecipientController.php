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
use AppBundle\Entity\Email\Recipient;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ParamConverter("recipient", options={"mapping": {"message": "message", "recipient": "id"}})
 * @Security("is_granted('VIEW', message)")
 * @View(serializerGroups={"Default", "recipient"}, serializerEnableMaxDepthChecks=true)
 */
class RecipientController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Message $message, Request $request)
    {
        return $this->paginate($message->getOriginalRecipients(), $request);
    }

    public function getAction(Message $message, Recipient $recipient)
    {
        return $recipient;
    }

    /**
     * @Get
     */
    public function childrenAction(Message $message, Recipient $recipient, Request $request)
    {
        return $this->paginate($recipient->getChildRecipients(), $request);
    }

    /**
     * @Security("is_granted('EDIT', message)")
     */
    public function deleteAction(Message $message, Recipient $recipient)
    {
        $this->getEntityManager()->remove($recipient);
        $this->getEntityManager()->flush();
    }

    /**
     * @Security("is_granted('EDIT', message)")
     */
    public function postAction(Message $message, Request $request)
    {
        $recipient = $this->deserializeRequest($request, Recipient::class);
        $message->addRecipient($recipient);
        $this->getEntityManager()->flush();
    }
}
