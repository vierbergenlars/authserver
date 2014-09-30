<?php

namespace App\Command;

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
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the user to add')
            ->addOption('super-admin', 's', InputOption::VALUE_NONE, 'Create a superadmin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getDoctrine()->getManagerForClass('AppBundle:User');

        $user = new User();
        $user->setUsername($input->getArgument('username'));
        $user->setEmail($input->getArgument('email'));

        $encoderFactory = $this->getService('security.encoder_factory');
        $encoder = $encoderFactory->getEncoder(get_class($user));
        $user->setPassword($encoder->encodePassword($input->getArgument('password'), $user->getSalt()));

        if($input->getOption('super-admin')) {
            $user->setRole('ROLE_SUPER_ADMIN');
        }

        $em->persist($user);
        $em->flush();
        $output->writeln(sprintf('User %s created', $input->getArgument('username')));
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private function getDoctrine()
    {
        return $this->getService('doctrine');
    }

    private function getService($id)
    {
        return $this->getApplication()->getKernel()->getContainer()->get($id);
    }
    }