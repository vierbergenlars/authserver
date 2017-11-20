<?php
namespace App\Command\Generate;

use App\AppEvents;

class GenerateMaintenanceCommand extends AbstractGenerateCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('app:generate:maintenance');
    }

    protected function getEventName()
    {
        return AppEvents::GENERATE_MAINTENANCE;
    }

    protected function getTargetFile()
    {
        return '_maintenance.html';
    }
}

