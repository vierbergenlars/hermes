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

use AppBundle\Entity\EmailAddress;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @View(serializerGroups={"Default", "sender"})
 */
class SenderController extends BaseController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $senderCandidates = $this->getEntityManager()
            ->getRepository(EmailAddress::class)
            ->createQueryBuilder('e')
            ->where('e.authCode IS NULL')
            ->getQuery()
            ->getResult();

        $authorizedSenders = array_filter($senderCandidates, function(EmailAddress $sender) {
            return $this->isGranted('USE', $sender);
        });

        return $this->paginate($authorizedSenders, $request);
    }

    /**
     * @Security("is_granted('USE', sender)")
     */
    public function getAction(EmailAddress $sender)
    {
        return $sender;
    }
}
