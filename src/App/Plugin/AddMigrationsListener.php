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


use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class AddMigrationsListener implements EventSubscriberInterface
{
    /**
     * @var Kernel
     */
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if($event->getCommand() instanceof AbstractCommand) {
            $style = new SymfonyStyle($event->getInput(), $event->getOutput());

            // Collect migrations from all registered bundles
            $style->section('Collect migrations');
            $fs = new Filesystem();
            $migrationDirs = [
                'core' => $this->kernel->getRootDir().'/DoctrineMigrations',
            ];
            $progressBar = $style->createProgressBar(count($this->kernel->getBundles()));
            foreach($this->kernel->getBundles() as $bundle)
            {
                $progressBar->setMessage('Bundle '.$bundle->getName());
                $bundleMigrationDir = $bundle->getPath().'/Resources/migrations';
                if(is_dir($bundleMigrationDir)) {
                    $migrationDirs[$bundle->getName()] = $bundleMigrationDir;
                }
                $progressBar->advance();
            }
            $progressBar->finish();
            $style->newLine();
            $style->listing(array_keys($migrationDirs));

            // Locate destination directory with migrations
            $destination = $this->kernel->getContainer()->getParameter('doctrine_migrations.dir_name');
            $fs->mkdir($destination);

            // Copy all migration files to destination
            // Separate directories so files do not overwrite each other
            // Copying is required because the migrations package does not follow symlinks
            $style->section('Mirror migrations');
            $progressBar = $style->createProgressBar(count($migrationDirs));
            foreach($migrationDirs as $name => $dir) {
                $progressBar->setMessage('Bundle '.$name);
                $fs->mirror($dir, $destination.'/'.$name);
                $progressBar->advance();
            }
            $progressBar->finish();
            $style->newLine(2);
        }
    }


}
