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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\User;

class PasswdCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:passwd')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The new password of the user')
            ->addOption('lock', 'l', InputOption::VALUE_NONE, 'Lock the user')
            ->addOption('unlock', 'u', InputOption::VALUE_NONE, 'Unlocks the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getService('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle:User');

        $user = $repo->findOneBy(array('username'=>$input->getArgument('username')));
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($input->getOption('lock')) {
            $user->setEnabled(false);
        } elseif ($input->getOption('unlock')) {
            $user->setEnabled(true);
        }

        if ($password = $input->getArgument('password')) {
            $encoderFactory = $this->getService('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder(get_class($user));
            $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
            $user->setPasswordEnabled(1);
        }

        $em->flush();
        $output->writeln(sprintf('User %s updated', $input->getArgument('username')));
    }

    private function getService($id)
    {
        return $this->getApplication()->getKernel()->getContainer()->get($id);
    }
}
