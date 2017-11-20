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

/**
 * Created by PhpStorm.
 * User: lars
 * Date: 19/08/17
 * Time: 19:10
 */
namespace App\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class HashVersioningStrategy implements VersionStrategyInterface
{

    private $webRoot;

    public function __construct($webRoot)
    {
        $this->webRoot = $webRoot;
    }

    /**
     * Returns the asset version for an asset.
     *
     * @param string $path
     *            A path
     *
     * @return string The version string
     */
    public function getVersion($path)
    {
        return sha1_file($this->webRoot . $path);
    }

    /**
     * Applies version to the supplied path.
     *
     * @param string $path
     *            A path
     *
     * @return string The versionized path
     */
    public function applyVersion($path)
    {
        return $path . '?' . $this->getVersion($path);
    }
}
