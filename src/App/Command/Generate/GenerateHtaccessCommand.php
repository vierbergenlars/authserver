<?php
namespace App\Command\Generate;

use App\AppEvents;

class GenerateHtaccessCommand extends AbstractGenerateCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('app:generate:htaccess');
    }

    protected function getEventName()
    {
        return AppEvents::GENERATE_HTACCESS;
    }

    protected function getTargetFile()
    {
        return 'web/.htaccess';
    }
}

