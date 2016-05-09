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

namespace AppBundle\Entity\Email;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AuthserverRecipient
 *
 * @ORM\Entity
 */
class AuthserverRecipient extends Recipient
{
    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="guid")
     * @Assert\NotBlank()
     * @Assert\Uuid()
     */
    private $userId;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return AuthserverRecipient
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function __toString()
    {
        return 'AuthserverRecipient('.$this->userId.')';
    }
}
