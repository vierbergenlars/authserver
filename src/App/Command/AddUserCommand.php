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

use App\Entity\EmailAddress;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\User;

class AddUserCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:adduser')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user to add')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user to add')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email address of the user to add')
            ->addOption('super-admin', 'a', InputOption::VALUE_NONE, 'Create a superadmin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getService('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle:User');

        $user = new User();
        $user->setUsername($input->getArgument('username'));
        $user->setDisplayname($input->getArgument('username'));
        $user->setPasswordEnabled(1);
        $user->setEnabled(true);
        if($input->getArgument('email')) {
            if(!$user->getEmailAddresses()->count())
                $user->addEmailAddress(new EmailAddress());
            $user->getEmailAddresses()->first()->setEmail($input->getArgument('email'));
        }

        $encoderFactory = $this->getService('security.encoder_factory');
        $encoder = $encoderFactory->getEncoder(get_class($user));
        $user->setPassword($encoder->encodePassword($input->getArgument('password'), $user->getSalt()));

        if ($input->getOption('super-admin')) {
            $user->setRole('ROLE_SUPER_ADMIN');
        }

        $em->persist($user);
        $em->flush();
        $output->writeln(sprintf('User %s created', $input->getArgument('username')));
    }

    private function getService($id)
    {
        return $this->getApplication()->getKernel()->getContainer()->get($id);
    }
}
