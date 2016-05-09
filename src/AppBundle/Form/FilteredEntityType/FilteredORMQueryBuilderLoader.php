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

namespace AppBundle\Form\FilteredEntityType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;

class FilteredORMQueryBuilderLoader extends ORMQueryBuilderLoader
{
    /**
     * @var callable
     */
    private $filter;

    public function __construct(QueryBuilder $queryBuilder, callable $filter)
    {
        parent::__construct($queryBuilder);
        $this->filter = $filter;
    }

    public function getEntities()
    {
        return array_filter(parent::getEntities(), $this->filter);
    }

    public function getEntitiesByIds($identifier, array $values)
    {
        return array_filter(parent::getEntitiesByIds($identifier, $values), $this->filter);
    }
}
