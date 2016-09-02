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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class QueuedMessageRepository extends EntityRepository
{
    /**
     * @return QueuedMessage[]
     */
    public function findSendableMessages($limit)
    {
        $baseQueryBuilder = $this->createQueryBuilder('message')
            ->where('message.sentAt IS NULL')
            ->andWhere('message.failedAt IS NULL')
        ;
        if($limit === null)
            return $baseQueryBuilder->getQuery()->getResult();

        $priorityDataBuilder = clone $baseQueryBuilder;
        $priorityData = $priorityDataBuilder
            ->select('COUNT(message) AS num', 'message.priority AS priority')
            ->groupBy('message.priority')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $availablePriorities = array_filter($priorityData, function($pd) {
            return $pd['num'] > 0;
        });

        usort($availablePriorities, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        $totalMessages = array_sum(array_map(function($pd) {
            return $pd['num'];
        }, $availablePriorities));

        $limit = min($limit, $totalMessages);

        $messagesPerPriority = [];
        while(array_sum($messagesPerPriority) < $limit) {
            foreach($availablePriorities as $pd) {
                if(!isset($messagesPerPriority[$pd['priority']]))
                    $messagesPerPriority[$pd['priority']] = 0;
                $messagesPerPriority[$pd['priority']]+=$pd['priority'];
                if($messagesPerPriority[$pd['priority']] >= $pd['num'])
                    $messagesPerPriority[$pd['priority']] = $pd['num'];
                if(array_sum($messagesPerPriority) >= $limit) {
                    $messagesPerPriority[$pd['priority']]-=(array_sum($messagesPerPriority)-$limit);
                    break;
                }
            }
        }

        $messages = [];

        $messagesQueryBuilder = $baseQueryBuilder
            ->andWhere('message.priority = :priority')
            ->orderBy('message.id', 'ASC');

        foreach($messagesPerPriority as $priority => $maxResults) {
            $extraMessages = $messagesQueryBuilder->getQuery()
                ->setMaxResults($maxResults)
                ->setParameter('priority', $priority)
                ->getResult();
            $messages = array_merge($messages, $extraMessages);
        }

        return $messages;
    }
}
