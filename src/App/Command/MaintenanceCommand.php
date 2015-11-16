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

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends Command {

    protected function configure()
    {
        $this->setName('app:maintenance')
                ->addOption('disable', 'd', InputOption::VALUE_NONE,
                            'Disable maintenance mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = __DIR__ . '/../../..';
        if ($input->getOption('disable')) {
            if (@unlink($dir . '/maintenance')) {
                $output->writeln('<info>Maintenance mode is disabled.</info>');
            } else {
                $output->writeln('<error>Error disabling maintenance mode</error>');
            }
        } else {
            $maintenanceText = <<<EOL
<!doctype html>
<html>
    <head>
        <title>503 Service unavailable</title>
    </head>
    <body>
        <h1>503 Service unavailable</h1>
        <hr>
        <p>Website is under maintenance.</p>
        <p>Please try again later.</p>
        <hr>
    </body>
</html>
EOL;
            if (file_exists($dir . '/_maintenance.html') && copy($dir . '/_maintenance.html',
                                                                 $dir . '/maintenance')
                    || file_put_contents($dir . '/maintenance', $maintenanceText)) {
                $output->writeln('<info>Maintenance mode is enabled.</info>');
            } else {
                $output->writeln('<error>Error enabling maintenance mode</error>');
            }
        }
    }

}
