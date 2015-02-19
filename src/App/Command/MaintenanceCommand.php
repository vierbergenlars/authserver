<?php

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
