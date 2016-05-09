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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class ApiUser extends User implements AutoAclInterface
{
    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Regex("/^[a-z0-9-]+$/")
     */
    protected $username;

    public function __construct()
    {
        $this->updatePassword();
    }

    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getRoles()
    {
        return [
            'ROLE_API'
        ];
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function updatePassword()
    {
        $this->password = base_convert(bin2hex(openssl_random_pseudo_bytes(32)), 16, 36);
    }

    public function getAclConfig()
    {
        return [
            'ROLE_ADMIN' => MaskBuilder::MASK_OPERATOR,
        ];
    }
}
