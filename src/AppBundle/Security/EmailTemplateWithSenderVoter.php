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

namespace AppBundle\Security;

use AppBundle\Entity\EmailTemplate;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\Security\Acl\Voter\AclVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmailTemplateWithSenderVoter extends Voter
{
    /**
     * @var AclVoter
     */
    private $aclVoter;

    /**
     * EmailTemplateWithSenderVoter constructor.
     *
     * @param AclVoter $aclVoter
     */
    public function __construct(AclVoter $aclVoter)
    {
        $this->aclVoter = $aclVoter;
    }

    protected function supports($attribute, $subject)
    {
        if($attribute !== 'EDIT')
            return false;
        if(!$subject instanceof EmailTemplate)
            return false;
        if(!$subject->getSender())
            return false;
        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if(!$subject instanceof EmailTemplate)
            return false;
        switch($attribute) {
            case 'EDIT':
                return $this->aclVoter->vote($token, $subject->getSender(), ['USE']) === self::ACCESS_GRANTED;
            default:
                return false;
        }
    }
}
