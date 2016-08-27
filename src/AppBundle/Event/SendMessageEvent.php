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

namespace AppBundle\Event;

use AppBundle\Entity\Email\Message;
use AppBundle\Entity\Email\QueuedMessage;
use AppBundle\Entity\Email\Recipient;
use Symfony\Component\EventDispatcher\Event;

class SendMessageEvent extends Event
{
    const EVENT_NAME = 'hermes.send_message';
    /**
     * @var QueuedMessage
     */
    private $message;

    /**
     * @var \Swift_Message
     */
    private $swiftMessage;

    /**
     * SendMessageEvent constructor.
     *
     * @param QueuedMessage $message
     * @param \Swift_Message $swiftMessage
     */
    public function __construct(QueuedMessage $message, \Swift_Message $swiftMessage = null)
    {
        $this->message = $message;
        $this->swiftMessage = $swiftMessage?:$message->getMimeMessage();
    }

    /**
     * @return QueuedMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \Swift_Message
     */
    public function getSwiftMessage()
    {
        return $this->swiftMessage;
    }

    /**
     * @param \Swift_Message $swiftMessage
     * @return SendMessageEvent
     */
    public function setSwiftMessage(\Swift_Message $swiftMessage)
    {
        $this->swiftMessage = $swiftMessage;

        return $this;
    }
}

