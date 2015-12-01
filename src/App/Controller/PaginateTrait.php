<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
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

namespace App\Controller;

use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait PaginateTrait
{
    /**
     * Gets a service by id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    abstract public function get($id);

    /**
     * @param $item
     * @param Request $request
     * @return PaginationInterface
     */
    protected function paginate($item, Request $request)
    {
        $page = (int)$request->query->get('page', 1);
        $size = (int)$request->query->get('per_page', 10);
        if($page <= 0)
            throw new BadRequestHttpException('The page parameter should be a positive number.');
        if($size <= 0)
            throw new BadRequestHttpException('The per_page parameter should be a positive number.');
        if($size > 1000)
            throw new BadRequestHttpException('The per_page parameter should not exceed 1000.');

        return $this->get('knp_paginator')->paginate($item, $page, $size);
    }
}
