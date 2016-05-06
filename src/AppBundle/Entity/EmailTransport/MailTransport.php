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

namespace AppBundle\Entity\EmailTransport;

use AppBundle\Form\EmailTransport\MailTransportType;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class MailTransport
 *
 * @ORM\Entity()
 */
class MailTransport extends EmailTransport
{
    const FORM_TYPE = MailTransportType::class;
    /**
     * @return \Swift_Transport
     */
    public function getSwiftTransport()
    {
        return \Swift_MailTransport::newInstance();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'mail';
    }
}

