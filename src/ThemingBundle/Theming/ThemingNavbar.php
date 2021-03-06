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

namespace ThemingBundle\Theming;


class ThemingNavbar
{
    use ConfigTrait;

    public function getBackgroundColor()
    {
        return $this->config['background'];
    }

    public function isInverse()
    {
        return $this->config['inverse'];
    }

    public function getCssClass()
    {
        return $this->isInverse()?'navbar-inverse':'navbar-default';
    }

    public function getVariablePrefix()
    {
        return $this->isInverse()?'navbar-inverse':'navbar-default';
    }

    public function getTextColor() {
        return $this->config['text_color'];
    }

    public function getLinkColor() {
        return $this->config['link_color']?:$this->getTextColor();
    }

    public function getLinkHoverColor() {
        return $this->config['link_hover_color'];
    }

    /**
     * @return array
     */
    public function getMenu() {
        return $this->config['menu'];
    }
}
