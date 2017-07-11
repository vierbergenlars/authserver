<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AuthRequestBundle\ExpressionLanguage;

use App\Entity\Group;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class AuthRequestExpressionLanguage extends ExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();
        $this->register('has_group', function($groupName) {
            return sprintf('array_filter($user->getGroupsRecursive(), function($group) {
                return $group->getName() === %s && $group->isExportable();
            }) !== []', $groupName);

        }, function(array $values, $groupName) {
            return array_filter($values['user']->getGroupsRecursive(), function(Group $group) use($groupName) {
                return $group->getName() === $groupName && $group->isExportable();
            }) !== [];
        });
    }
}
