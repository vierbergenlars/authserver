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

namespace App\Plugin;


class PhpPluginDumper
{
    private $autoloaders;
    private $bundles;

    public function __construct($bundles, $autoloaders)
    {
        $this->bundles = $bundles;
        $this->autoloaders = $autoloaders;
    }

    public function dump()
    {
        return <<<EOF
<?php

{$this->generateAutoloaders()};

return {$this->generateBundlesArray()};
EOF;

    }

    private function generateAutoloaders()
    {
        return implode(";\n", array_map(function($autoloader) {
            return sprintf('require %s', var_export($autoloader, true));
        }, $this->autoloaders));
    }

    private function generateBundlesArray()
    {
        return sprintf('array(%s)', implode(",\n", array_map(function($bundle) {
            return sprintf('new %s()', $bundle);
        }, $this->bundles)));
    }
}
