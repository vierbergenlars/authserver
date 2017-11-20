<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015 Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace App\Command\Generate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Event\TemplateEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractGenerateCommand extends Command
{

    protected function configure()
    {
        $this->setDescription('Generate ' . $this->getTargetFile())
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Writes the resulting file to stdout instead of directly to the target file.');
    }

    abstract protected function getTargetFile();

    abstract protected function getEventName();

    protected function getBaseTemplate()
    {
        return 'AppBundle::templateEvent.html.twig';
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventDispatcher = $this->getService('event_dispatcher');

        $templateEvent = new TemplateEvent($input);

        $eventDispatcher->dispatch($this->getEventName(), $templateEvent);

        $twig = $this->getService('twig');
        /* @var $twig \Twig_Environment */

        $data = $twig->render($this->getBaseTemplate(), [
            'event' => $templateEvent
        ]);

        if ($input->getOption('stdout')) {
            $output->write($data, OutputInterface::OUTPUT_RAW);
        } else {
            $fs = new Filesystem();
            $fs->dumpFile($this->getKernel()
                ->getRootDir() . '/../' . $this->getTargetFile(), $data);
            $output->writeln('Generated ' . $this->getTargetFile(), OutputInterface::OUTPUT_NORMAL);
        }
    }

    /**
     *
     * @return KernelInterface
     */
    private function getKernel()
    {
        return $this->getApplication()->getKernel();
    }

    private function getService($id)
    {
        return $this->getKernel()
            ->getContainer()
            ->get($id);
    }
}
