<?php

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
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'The roles to add to the user, and the ones to remove from the user (prefixed by -)')
            ->addOption('lock', 'l', InputOption::VALUE_NONE, 'Lock the user')
            ->addOption('unlock', 'u', InputOption::VALUE_NONE, 'Unlocks the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getDoctrine()->getManagerForClass('AppBundle:User');

        $user = $em->getRepository('AppBundle:User')->findOneBy(array('username'=>$input->getArgument('username')));
        if(!$user) {
            throw new \RuntimeException('User not found');
        }
        foreach($input->getOption('role') as $role) {
            if($role[0] == '-') {
                $user->removeRole(substr($role, 1));
            } else {
                $user->addRole($role);
            }
        }

        if($input->getOption('lock')) {
            $user->setEnabled(false);
        } else if($input->getOption('unlock')) {
            $user->setEnabled(true);
        }

        if($password = $input->getArgument('password')) {
            $encoderFactory = $this->getService('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder(get_class($user));
            $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
        }

        $em->flush();
        $output->writeln(sprintf('User %s updated', $input->getArgument('username')));
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