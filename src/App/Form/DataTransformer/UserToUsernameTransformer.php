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

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserToUsernameTransformer implements DataTransformerInterface
{
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repo;

    /**
     *
     * @param \Doctrine\ORM\EntityRepository $repo
     */
    public function __construct(EntityRepository $repo)
    {
        $this->repo = $repo;
    }

    public function transform($user)
    {
        if (null === $user) {
            return '';
        }

        if (is_string($user)) {
            return $user;
        }

        return $user->getUsername();
    }

    public function reverseTransform($value)
    {
        if(!$value)

            return null;
        $user = $this->repo->findOneByUsername($value);
        if (null === $user) {
            throw new TransformationFailedException('User does not exist '.$value);
        }

        return $user;
    }
}
