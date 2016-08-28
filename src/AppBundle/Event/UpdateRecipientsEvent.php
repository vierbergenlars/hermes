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

class UpdateRecipientsEvent extends Event
{
    const EVENT_NAME = 'hermes.update_recipients';
    /**
     * @var Recipient
     */
    private $recipient;

    /**
     * UpdateRecipientsEvent constructor.
     *
     * @param Recipient $recipient
     */
    public function __construct(Recipient $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @return Recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }
}

