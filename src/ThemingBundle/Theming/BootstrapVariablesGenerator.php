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

class BootstrapVariablesGenerator
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $template;

    /**
     * @var ThemingRoot
     */
    private $theming;

    /**
     * BootstrapVariablesGenerator constructor.
     *
     * @param \Twig_Environment $twig
     * @param string $template
     * @param ThemingRoot $theming
     */
    public function __construct(\Twig_Environment $twig, $template, ThemingRoot $theming)
    {
        $this->twig = $twig;
        $this->template= $template;
        $this->theming = $theming;
    }

    public function getVariablesFile()
    {
        return $this->twig->render(
            $this->template,
            [
                'theming' => $this->theming,
            ]
        );
    }
}
