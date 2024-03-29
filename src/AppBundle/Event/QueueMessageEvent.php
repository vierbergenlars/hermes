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
use AppBundle\Entity\Email\Recipient;
use Symfony\Component\EventDispatcher\Event;

class QueueMessageEvent extends Event
{
    const EVENT_NAME = 'hermes.queue_message';
    /**
     * @var Message
     */
    private $message;

    /**
     * @var Recipient
     */
    private $recipient;

    /**
     * @var array
     */
    private $extraTemplateParameters;

    /**
     * QueueMessageEvent constructor.
     *
     * @param Message $message
     * @param Recipient $recipient
     * @param array $extraTemplateParameters
     */
    public function __construct(Message $message, Recipient $recipient, array $extraTemplateParameters = [])
    {
        $this->message = $message;
        $this->recipient = $recipient;
        $this->extraTemplateParameters = $extraTemplateParameters;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return Recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    public function getExtraTemplateParameters()
    {
        return $this->extraTemplateParameters;
    }
}

