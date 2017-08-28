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


use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Fetches and constructs plugin bundles from a specified directory.
 *
 * @package App\Plugin
 */
class PluginBundleFetcher
{
    /**
     * @var string
     */
    private $pluginDir;

    /**
     * @var ConfigCacheInterface
     */
    private $cache;


    // Internal caching

    /**
     * @var SplFileInfo[]|null
     */
    private $pluginBundleFiles;
    private $autoloaders;
    private $bundleClasses;


    public function __construct($pluginDir, ConfigCacheInterface $cache = null)
    {
        $this->pluginDir = $pluginDir;
        $this->cache = $cache;
    }

    /**
     * Gets the list of files ending with `Bundle.php`.
     *
     * These files contain a Symfony Bundle that will be loaded as a plugin.
     *
     * @return SplFileInfo[]
     */
    private function getPluginBundleFiles()
    {
        if($this->pluginBundleFiles !== null)
            return $this->pluginBundleFiles;

        if(!is_dir($this->pluginDir))
            return [];

        $pluginBundles = Finder::create()
            ->in($this->pluginDir)
            ->ignoreVCS(true)
            ->exclude('vendor')
            ->name('*Bundle.php')
            ->followLinks()
        ;

        return $this->pluginBundleFiles = iterator_to_array($pluginBundles);
    }

    /**
     * Gets the list of directories containing Plugin Bundles
     *
     * @return string[]
     */
    private function getPluginDirs()
    {
        return array_map(function(SplFileInfo $bundleFile) {
            return $bundleFile->getPath();
        }, $this->getPluginBundleFiles());
    }

    /**
     * Gets the list of autoloaders that are defined in all Plugin Bundles
     *
     * @return string[] Paths to files containing autoloaders for Plugin Bundles
     */
    private function getAutoloaders()
    {
        if($this->autoloaders !== null)
            return $this->autoloaders;
        $this->autoloaders = [];
        $autoloaderLocations = [
            '/autoload.php',
            '/vendor/autoload.php'
        ];
        foreach($this->getPluginDirs() as $pluginDir) {
            foreach($autoloaderLocations as $autoloaderLocation) {
                if(file_exists($pluginDir.$autoloaderLocation)) {
                    $this->autoloaders[] = $pluginDir . $autoloaderLocation;
                }
            }
        }
        return $this->autoloaders;
    }

    /**
     * Get the list of Bundle classes that are defined inside the *Bundle.php files.
     *
     * @return string[] Class names of Bundles
     */
    private function getBundleClasses()
    {
        if($this->bundleClasses !== null)
            return $this->bundleClasses;
        return $this->bundleClasses = array_map(function(SplFileInfo $bundleFile) {
            return self::getDefinedClassInFile($bundleFile->getPathname());
        }, $this->getPluginBundleFiles());
    }

    /**
     * Gets the class that is defined in a file.
     *
     * @param string $file The file to fetch the defined class from.
     * @return string The name of the class defined in the file, including its full namespace.
     */
    private static function getDefinedClassInFile($file)
    {
        $parser = new TokenParser(file_get_contents($file));

        $definedClasses = $parser->getDefinedClasses();

        switch(count($definedClasses)) {
            case 0:
                throw new \LogicException(sprintf('File "%s" does not contain any classes.', $file));
            default:
                throw new \LogicException(sprintf('File "%s" should only contain one class.', $file));
            case 1:
                return $definedClasses[0];
        }
    }

    /**
     * Gets the list of all instanciated Bundle classes that have been found inside the plugin directory.
     *
     * @return BundleInterface[]
     */
    public function getBundles()
    {
        if(!$this->cache) {
            foreach($this->getAutoloaders() as $autoloader) {
                require $autoloader;
            }
            return array_map(function($bundleClass) {
                return new $bundleClass();
            }, $this->getBundleClasses());
        }

        if(!$this->cache->isFresh()) {
            $dumper = new PhpPluginDumper($this->getBundleClasses(), $this->getAutoloaders());
            $this->cache->write($dumper->dump());
        }

        return require $this->cache->getPath();
    }
}
