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

namespace ThemingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ThemingBundle\Theming\BootstrapVariablesGenerator;

class GenerateBootstrapCommand extends ContainerAwareCommand
{
    private $variablesGenerator;
    private $destinationFile;

    public function __construct(BootstrapVariablesGenerator $variablesGenerator, $destinationFile)
    {
        parent::__construct();
        $this->variablesGenerator = $variablesGenerator;
        $this->destinationFile = $destinationFile;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('theming:generate:bootstrap');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!@mkdir(dirname($this->destinationFile), 0777, true) && !is_dir(dirname($this->destinationFile)))
            throw new \RuntimeException('Can not create directory '.dirname($this->destinationFile));
        $this->getApplication()->find('braincrafted:bootstrap:generate')->run($input, $output);
        file_put_contents($this->destinationFile, $this->variablesGenerator->getVariablesFile());
    }
}
